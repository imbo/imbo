<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo;

use Imbo\Http\Request\Request,
    Imbo\Http\Response\Response,
    Imbo\Http\Response\ResponseFormatter,
    Imbo\Http\Response\ResponseWriter,
    Imbo\EventListener\ListenerInterface,
    Imbo\EventListener\ListenerDefinition,
    Imbo\EventManager\Event,
    Imbo\EventManager\EventManager,
    Imbo\Model\Image,
    Imbo\Model\Error,
    Imbo\Image\ImagePreparation,
    Imbo\Exception\RuntimeException,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Database\DatabaseInterface,
    Imbo\Storage\StorageInterface,
    Imbo\Resource\Images\Query,
    Imbo\Http\Response\Formatter,
    Imbo\Image\Transformation;

/**
 * Imbo application
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Core
 */
class Application {
    /**
     * Application configuration
     *
     * @var array
     */
    private $config;

    /**
     * Service container
     *
     * @var Container
     */
    private $container;

    /**
     * Run the application
     */
    public function run() {
        if (!$this->container) {
            throw new RuntimeException('Application has not been bootstrapped', 500);
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
                throw new RuntimeException('Unknown resource', 500);
            }

            $resource = $this->container->get($entry);

            // Inform the user agent of which methods are allowed against this resource
            $response->headers->set('Allow', implode(', ', $resource->getAllowedMethods()));

            // Fetch auth config
            $config = $this->container->get('config');
            $authConfig = $config['auth'];
            $publicKey = $request->getPublicKey();

            // See if the public key exists
            if ($publicKey) {
                if (!isset($authConfig[$publicKey])) {
                    $e = new RuntimeException('Unknown public key', 404);
                    $e->setImboErrorCode(Exception::AUTH_UNKNOWN_PUBLIC_KEY);

                    throw $e;
                }

                // Fetch the private key from the config and store it in the request
                $privateKey = $authConfig[$publicKey];
                $request->setPrivateKey($privateKey);
            }

            $methodName = strtolower($request->getMethod());

            $resource = $request->getResource();
            $eventManager->trigger($resource);

            // Generate the event name based on the accessed resource and the HTTP method
            $eventName = $resource . '.' . $methodName;

            if (!$eventManager->hasListenersForEvent($eventName)) {
                throw new RuntimeException('Method not allowed', 405);
            }

            $eventManager->trigger($eventName);
        } catch (Exception $exception) {
            // An error has occured. Create an error and send the response
            $error = Error::createFromException($exception, $this->container->get('request'));
            $this->container->get('response')->setError($error);
        }

        $eventManager->trigger('response.send');
    }

    /**
     * Bootstrap the container
     *
     * @param array $config Imbo configuration
     * @param Container $container Optional container instance
     * @return Application
     */
    public function bootstrap(array $config, Container $container = null) {
        if (!$container) {
            $container = new Container();
        }

        // Main configuration
        $container->set('config', $config);

        // Query object used when querying for images
        $container->set('imagesQuery', function(Container $container) {
            return new Query();
        });

        // Date formatter helper
        $container->set('dateFormatter', new Helpers\DateFormatter());

        // Request from the client
        $container->set('request', Request::createFromGlobals());

        // Response to the client
        $container->setStatic('response', function(Container $container) {
            $response = new Response();

            $response->setImage($container->get('image'))
                     ->setPublic();
            $response->headers->set('X-Imbo-Version', Version::VERSION);

            return $response;
        });

        // Event object
        $container->set('event', function(Container $container) {
            $event = new Event();
            $event->setContainer($container);

            return $event;
        });

        // Response writer
        $container->setStatic('responseWriter', function(Container $container) {
            $writer = new ResponseWriter();
            $writer->setContainer($container);

            return $writer;
        });

        // Response formatter
        $container->setStatic('responseFormatter', function(Container $container) {
            $formatter = new ResponseFormatter();
            $formatter->setContainer($container);

            return $formatter;
        });

        // Image instance that will be attached to the request or the response instances
        $container->set('image', function(Container $container) {
            return new Image();
        });

        // Content negotiation component
        $container->set('contentNegotiation', new Http\ContentNegotiation());

        // Image preparation component
        $container->setStatic('imagePreparation', function(Container $container) {
            $preparation = new ImagePreparation();
            $preparation->setContainer($container);

            return $preparation;
        });

        // Metadata resource
        $container->setStatic('metadataResource', function(Container $container) {
            $resource = new Resource\Metadata();

            return $resource;
        });

        // Images resource
        $container->setStatic('imagesResource', function(Container $container) {
            $resource = new Resource\Images();

            return $resource;
        });

        // User resource
        $container->setStatic('userResource', function(Container $container) {
            $resource = new Resource\User();

            return $resource;
        });

        // Status resource
        $container->setStatic('statusResource', function(Container $container) {
            $resource = new Resource\Status();
            $resource->setContainer($container);

            return $resource;
        });

        // Image resource
        $container->setStatic('imageResource', function(Container $container) {
            $resource = new Resource\Image();

            return $resource;
        });

        // Image transformer listener
        $container->setStatic('imageTransformer', function(Container $container) {
            $transformer = new EventListener\ImageTransformer();

            $config = $container->get('config');

            foreach ($config['imageTransformations'] as $name => $callback) {
                $transformer->registerTransformationHandler($name, $callback);
            }

            return $transformer;
        });

        // Response sender
        $container->setStatic('responseSender', function(Container $container) {
            return new EventListener\ResponseSender();
        });

        // Router component
        $container->setStatic('router', function(Container $container) {
            $router = new Router();

            return $router;
        });

        // Database adapter
        $container->setStatic('database', function(Container $container) {
            $config = $container->get('config');
            $adapter = $config['database'];

            if (is_callable($adapter) && !($adapter instanceof DatabaseInterface)) {
                $adapter = $adapter();
            }

            if (!$adapter instanceof DatabaseInterface) {
                throw new InvalidArgumentException('Invalid database adapter', 500);
            }

            return $adapter;
        });

        // Storage adapter
        $container->setStatic('storage', function(Container $container) {
            $config = $container->get('config');
            $adapter = $config['storage'];

            if (is_callable($adapter) && !($adapter instanceof StorageInterface)) {
                $adapter = $adapter();
            }

            if (!$adapter instanceof StorageInterface) {
                throw new InvalidArgumentException('Invalid storage adapter', 500);
            }

            return $adapter;
        });

        // Database operations listener
        $container->setStatic('databaseOperations', function(Container $container) {
            $listener = new EventListener\DatabaseOperations();
            $listener->setContainer($container);

            return $listener;
        });

        // Storage operations listener
        $container->setStatic('storageOperations', function(Container $container) {
            return new EventListener\StorageOperations();
        });

        // Event manager component
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
                'responseFormatter',
                'router',
                'databaseOperations',
                'storageOperations',
                'imagePreparation',
                'imageTransformer',
                'responseSender',
            );

            foreach ($containerEntries as $listener) {
                $manager->attachListener($container->get($listener));
            }

            $config = $container->get('config');
            $listeners = $config['eventListeners'];

            foreach ($listeners as $definition) {
                if (!$definition) {
                    continue;
                }

                if (is_callable($definition) && !($definition instanceof ListenerInterface)) {
                    $definition = $definition();
                }

                if ($definition instanceof ListenerInterface) {
                    $manager->attachListener($definition);
                    continue;
                }

                if (!empty($definition['listener'])) {
                    $publicKeys = isset($definition['publicKeys']) ? $definition['publicKeys'] : array();
                    $listener = $definition['listener'];

                    if (is_callable($listener) && !($listener instanceof ListenerInterface)) {
                        $listener = $listener();
                    }

                    if (!$listener instanceof ListenerInterface) {
                        throw new InvalidArgumentException('Invalid event listener definition', 500);
                    }

                    if (empty($publicKeys)) {
                        $manager->attachListener($listener);
                    } else {
                        $definition = $listener->getDefinition();

                        foreach ($definition as $d) {
                            $d->setPublicKeys($publicKeys);
                            $manager->attachDefinition($d);
                        }
                    }
                } else if (!empty($definition['callback']) && !empty($definition['events'])) {
                    $callback = $definition['callback'];
                    $priority = isset($definition['priority']) ? $definition['priority'] : 1;
                    $publicKeys = isset($definition['publicKeys']) ? $definition['publicKeys'] : array();

                    foreach ($definition['events'] as $key => $value) {
                        $event = $value;

                        if (is_string($key)) {
                            // We have an associative array with <event> => <priority>
                            $event = $key;
                            $priority = $value;
                        }

                        $manager->attach($event, $callback, $priority, $publicKeys);
                    }
                } else {
                    throw new InvalidArgumentException('Invalid event listener definition', 500);
                }
            }

            return $manager;
        });

        // Formatters
        $container->setStatic('jsonFormatter', function(Container $container) {
            return new Formatter\JSON($container->get('dateFormatter'));
        });
        $container->setStatic('htmlFormatter', function(Container $container) {
            return new Formatter\HTML($container->get('dateFormatter'));
        });
        $container->setStatic('xmlFormatter', function(Container $container) {
            return new Formatter\XML($container->get('dateFormatter'));
        });
        $container->setStatic('gifFormatter', function(Container $container) {
            return new Formatter\Gif(new Transformation\Convert(array('type' => 'gif')));
        });
        $container->setStatic('jpegFormatter', function(Container $container) {
            return new Formatter\Jpeg(new Transformation\Convert(array('type' => 'jpg')));
        });
        $container->setStatic('pngFormatter', function(Container $container) {
            return new Formatter\Png(new Transformation\Convert(array('type' => 'png')));
        });

        $this->container = $container;

        return $this;
    }
}
