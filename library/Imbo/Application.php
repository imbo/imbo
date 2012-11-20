<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package Core
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo;

use Imbo\Http\Request\Request,
    Imbo\Http\Response\Response,
    Imbo\Http\Response\ResponseFormatter,
    Imbo\Http\Response\ResponseWriter,
    Imbo\EventManager\EventManager,
    Imbo\Image\Image,
    Imbo\EventManager\Event,
    Imbo\Exception\RuntimeException,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Database\DatabaseInterface,
    Imbo\Storage\StorageInterface;

/**
 * Imbo application
 *
 * @package Core
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Application {
    /**
     * @var array
     */
    private $config;

    /**
     * @var Container
     */
    private $container;

    /**
     * Class constructor
     *
     * @param array $config Main Imbo configuration
     * @param Container $container Pre-bootstrapped container
     */
    public function __construct(array $config, Container $container = null) {
        $this->config = $config;
        $this->container = $container;
    }

    /**
     * Run the application
     */
    public function run() {
        if (!$this->container) {
            $this->bootstrap();
        }

        $eventManager = $this->container->get('eventManager');
        $request = $this->container->get('request');
        $response = $this->container->get('response');

        try {
            // Route the request
            $eventManager->trigger('route');

            // Fetch the name of the current resource
            $resource = $request->getResource();
            $entry = $resource . 'Resource';

            if (!$this->container->has($entry)) {
                throw new RuntimeException('Unknown Resource', 500);
            }

            $resource = $this->container->get($entry);

            // Add some response headers
            $responseHeaders = $response->getHeaders();

            // Inform the user agent of which methods are allowed against this resource
            $responseHeaders->set('Allow', implode(', ', $resource->getAllowedMethods()));

            // Add Accept to Vary if the client has not specified a specific extension, in which we
            // won't do any content negotiation at all.
            if (!$request->getExtension()) {
                $responseHeaders->set('Vary', 'Accept');
            }

            // Fetch the real image identifier (PUT only) or the one from the URL (if present)
            if (($identifier = $request->getRealImageIdentifier()) ||
                ($identifier = $request->getImageIdentifier())) {
                $responseHeaders->set('X-Imbo-ImageIdentifier', $identifier);
            }

            // Fetch auth config
            $config = $this->container->get('config');
            $authConfig = $config['auth'];
            $publicKey = $request->getPublicKey();

            // See if the public key exists
            if ($publicKey) {
                if (!isset($authConfig[$publicKey])) {
                    $e = new RuntimeException('Unknown Public Key', 404);
                    $e->setImboErrorCode(Exception::AUTH_UNKNOWN_PUBLIC_KEY);

                    throw $e;
                }

                // Fetch the private key from the config and store it in the request
                $privateKey = $authConfig[$publicKey];
                $request->setPrivateKey($privateKey);
            }

            $methodName = strtolower($request->getMethod());

            // Generate the event name based on the accessed resource and the HTTP method
            $eventName = $request->getResource() . '.' . $methodName;

            if (!$eventManager->hasListenersForEvent($eventName)) {
                throw new RuntimeException('Method not allowed', 405);
            }

            $eventManager->trigger($eventName);
        } catch (Exception $exception) {
            // An error has occured. Create an error and send the response
            $this->container->get('response')->createError(
                $exception,
                $this->container->get('request')
            );
        }

        $eventManager->trigger('response.send');
    }

    /**
     * Bootstrap the container
     */
    private function bootstrap() {
        $container = new Container();

        $container->set('config', $this->config);
        $container->set('request', new Request($_GET, $_POST, $_SERVER));
        $container->set('version', new Version());
        $container->setStatic('response', function ($container) {
            $response = new Response();
            $response->getHeaders()->set(
                'X-Imbo-Version',
                $container->get('version')->getVersionNumber()
            );

            return $response;
        });
        $container->set('event', function(Container $container, array $params) {
            $event = new Event($params['name'], $params['params']);
            $event->setContainer($container);

            return $event;
        });
        $container->setStatic('responseWriter', function(Container $container) {
            $writer = new ResponseWriter();
            $writer->setContainer($container);

            return $writer;
        });
        $container->setStatic('responseFormatter', function(Container $container) {
            $formatter = new ResponseFormatter();
            $formatter->setContainer($container);

            return $formatter;
        });
        $container->set('image', new Image());
        $container->set('contentNegotiation', new Http\ContentNegotiation());
        $container->set('metadataResource', new Resource\Metadata());
        $container->set('imagesResource', new Resource\Images());
        $container->set('userResource', new Resource\User());
        $container->set('statusResource', new Resource\Status());
        $container->setStatic('imageResource', function(Container $container) {
            $imageResource = new Resource\Image($container->get('image'));
            $config = $container->get('config');

            foreach ($config['transformations'] as $name => $callback) {
                $imageResource->registerTransformationHandler($name, $callback);
            }

            return $imageResource;
        });
        $container->setStatic('router', function(Container $container) {
            $router = new Router();
            $router->setContainer($container);

            return $router;
        });
        $container->setStatic('database', function(Container $container) {
            $imboConfig = $container->get('config');
            $config = $imboConfig['database'];
            $driver = $config['driver'];

            if (is_string($driver)) {
                if (!empty($config['params'])) {
                    $driver = new $driver($config['params']);
                } else {
                    $driver = new $driver();
                }
            }

            if (!$driver instanceof DatabaseInterface) {
                throw new InvalidArgumentException('Invalid database driver', 500);
            }

            return $driver;
        });
        $container->setStatic('storage', function(Container $container) {
            $imboConfig = $container->get('config');
            $config = $imboConfig['storage'];
            $driver = $config['driver'];

            if (is_string($driver)) {
                if (!empty($config['params'])) {
                    $driver = new $driver($config['params']);
                } else {
                    $driver = new $driver();
                }
            }

            if (!$driver instanceof StorageInterface) {
                throw new InvalidArgumentException('Invalid storage driver', 500);
            }

            return $driver;
        });
        $container->setStatic('eventManager', function(Container $container) {
            $manager = new EventManager();
            $manager->setContainer($container);

            // Register internal event listeners
            $manager->attachListener($container->get('statusResource'), 50)
                    ->attachListener($container->get('userResource'), 50)
                    ->attachListener($container->get('imagesResource'), 50)
                    ->attachListener($container->get('imageResource'), 50)
                    ->attachListener($container->get('metadataResource'), 50)
                    ->attachListener($container->get('response'), 50)
                    ->attachListener($container->get('responseFormatter'), 60)
                    ->attachListener($container->get('router'), 50);

            // Register event listeners from the configuration
            $config = $container->get('config');
            $listeners = $config['eventListeners'];

            foreach ($listeners as $definition) {
                if ($definition instanceof ListenerInterface) {
                    $manager->attachListener($definition);
                    continue;
                }

                if (!is_array($definition) || empty($definition['listener'])) {
                    throw new InvalidArgumentException('Missing listener definition', 500);
                }

                $listener = $definition['listener'];
                $priority = isset($definition['priority']) ? $definition['priority'] : 1;

                if ($listener instanceof ListenerInterface) {
                    if (
                        $listener instanceof PublicKeyAwareListenerInterface &&
                        !empty($definition['publicKeys']) &&
                        is_array($definition['publicKeys'])
                    ) {
                        $listener->setPublicKeys($definition['publicKeys']);
                    }

                    $manager->attachListener($listener, $priority);
                } else if (is_callable($listener) && !empty($definition['events']) && is_array($definition['events'])) {
                    $manager->attach($definition['events'], $listener, $priority);
                } else {
                    throw new InvalidArgumentException('Invalid listener', 500);
                }
            }

            return $manager;
        });

        $this->container = $container;
    }
}
