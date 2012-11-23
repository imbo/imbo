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
    Imbo\EventListener\ListenerInterface,
    Imbo\EventManager\EventManager,
    Imbo\EventManager\Event,
    Imbo\Image\Image,
    Imbo\Image\ImagePreparation,
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
        $container->set('dateFormatter', new Helpers\DateFormatter());
        $container->set('request', new Request($_GET, $_POST, $_SERVER));
        $container->set('version', new Version());
        $container->setStatic('response', function ($container) {
            $response = new Response();
            $response->setImage($container->get('image'));
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
        $container->set('imagePreparation', new ImagePreparation());
        $container->setStatic('metadataResource', function(Container $container) {
            $resource = new Resource\Metadata();
            $resource->setContainer($container);

            return $resource;
        });
        $container->setStatic('imagesResource', function(Container $container) {
            $resource = new Resource\Images();
            $resource->setContainer($container);

            return $resource;
        });
        $container->setStatic('userResource', function(Container $container) {
            $resource = new Resource\User();
            $resource->setContainer($container);

            return $resource;
        });
        $container->setStatic('statusResource', function(Container $container) {
            $resource = new Resource\Status();
            $resource->setContainer($container);

            return $resource;
        });
        $container->setStatic('imageResource', function(Container $container) {
            $resource = new Resource\Image();
            $resource->setContainer($container);

            $config = $container->get('config');

            foreach ($config['transformations'] as $name => $callback) {
                $resource->registerTransformationHandler($name, $callback);
            }

            return $resource;
        });
        $container->setStatic('router', function(Container $container) {
            $router = new Router();
            $router->setContainer($container);

            return $router;
        });
        $container->setStatic('database', function(Container $container) {
            $config = $container->get('config');
            $adapter = $config['database'];

            if (!$adapter instanceof DatabaseInterface) {
                throw new InvalidArgumentException('Invalid database adapter', 500);
            }

            return $adapter;
        });
        $container->setStatic('storage', function(Container $container) {
            $config = $container->get('config');
            $adapter = $config['storage'];

            if (!$adapter instanceof StorageInterface) {
                throw new InvalidArgumentException('Invalid storage adapter', 500);
            }

            return $adapter;
        });
        $container->setStatic('databaseOperations', function(Container $container) {
            return new EventListener\DatabaseOperations($container->get('database'));
        });
        $container->setStatic('storageOperations', function(Container $container) {
            return new EventListener\StorageOperations($container->get('storage'));
        });
        $container->setStatic('eventManager', function(Container $container) {
            $manager = new EventManager();
            $manager->setContainer($container);

            // Register internal event listeners
            $containerEntries = array(
                'statusResource',
                'userResource',
                'imagesResource',
                'imageResource',
                'metadataResource',
                'response',
                'responseFormatter',
                'router',
                'databaseOperations',
                'storageOperations',
                'imagePreparation',
            );

            foreach ($containerEntries as $listener) {
                $container->get($listener)->attach($manager);
            }

            $config = $container->get('config');
            $listeners = $config['eventListeners'];

            foreach ($listeners as $definition) {
                if ($definition instanceof ListenerInterface) {
                    $definition->attach($manager);
                    continue;
                }

                if (!is_array($definition) || empty($definition['callback']) || empty($definition['events'])) {
                    throw new InvalidArgumentException('Invalid event listener definition', 500);
                }

                $callback = $definition['callback'];
                $priority = isset($definition['priority']) ? $definition['priority'] : 1;

                foreach ($definition['events'] as $key => $value) {
                    $event = $value;

                    if (is_string($key)) {
                        // We have an associative array with <event> => <priority>
                        $event = $key;
                        $priority = $value;
                    }

                    $manager->attach($event, $callback, $priority);
                }
            }

            return $manager;
        });

        $this->container = $container;
    }
}
