<?php
namespace Imbo;

use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\EventListener\ListenerInterface;
use Imbo\EventManager\Event;
use Imbo\EventManager\EventManager;
use Imbo\Model\Error;
use Imbo\Auth\AccessControl\Adapter\AdapterInterface as AccessControlInterface;
use Imbo\Exception\RuntimeException;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Database\DatabaseInterface;
use Imbo\Storage\StorageInterface;
use Imbo\Http\Response\Formatter;
use Imbo\Resource\ResourceInterface;
use Imbo\Image\TransformationManager;
use Imbo\EventListener\Initializer\InitializerInterface;
use Imbo\Image\InputLoaderManager;
use Imbo\Image\OutputConverterManager;

/**
 * Imbo application
 */
class Application {
    private array $config;
    private Request $request;
    private Response $response;

    public function __construct(array $config) {
        $this->config = $config;

        $this->request = Request::createFromGlobals();
        Request::setTrustedProxies(
            $config['trustedProxies'],
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO,
        );

        $this->response = (new Response())->setPublic();
        $this->response->headers->set('X-Imbo-Version', Version::VERSION);
    }

    public function run(): void {
        $database = $this->config['database'];

        if (is_callable($database) && !($database instanceof DatabaseInterface)) {
            $database = $database($this->request, $this->response);
        }

        if (!$database instanceof DatabaseInterface) {
            throw new InvalidArgumentException('Invalid database adapter', 500);
        }

        $storage = $this->config['storage'];

        if (is_callable($storage) && !($storage instanceof StorageInterface)) {
            $storage = $storage($this->request, $this->response);
        }

        if (!$storage instanceof StorageInterface) {
            throw new InvalidArgumentException('Invalid storage adapter', 500);
        }

        // Access control adapter
        $accessControl = $this->config['accessControl'];

        if (is_callable($accessControl) && !($accessControl instanceof AccessControlInterface)) {
            $accessControl = $accessControl($this->request, $this->response);
        }

        if (!$accessControl instanceof AccessControlInterface) {
            throw new InvalidArgumentException('Invalid access control adapter', 500);
        }

        // Create a router based on the routes in the configuration and internal routes
        $router = new Router($this->config['routes']);

        // Create a new image transformation manager
        $transformationManager = new TransformationManager();

        if (isset($this->config['transformations']) && !is_array($this->config['transformations'])) {
            throw new InvalidArgumentException('The "transformations" configuration key must be specified as an array', 500);
        } else if (isset($this->config['transformations']) && is_array($this->config['transformations'])) {
            $transformationManager->addTransformations($this->config['transformations']);
        }

        // Create a loader manager and register any loaders
        $inputLoaderManager = new InputLoaderManager();

        if (isset($this->config['inputLoaders']) && !is_array($this->config['inputLoaders'])) {
            throw new InvalidArgumentException('The "inputLoaders" configuration key must be specified as an array', 500);
        } else if (isset($this->config['inputLoaders']) && is_array($this->config['inputLoaders'])) {
            $inputLoaderManager->addLoaders($this->config['inputLoaders']);
        }

        // Create a output conversion manager and register any converters
        $outputConverterManager = new OutputConverterManager();

        if (isset($this->config['outputConverters']) && !is_array($this->config['outputConverters'])) {
            throw new InvalidArgumentException('The "outputConverters" configuration key must be specified as an array', 500);
        } else if (isset($this->config['outputConverters']) && is_array($this->config['outputConverters'])) {
            $outputConverterManager->addConverters($this->config['outputConverters']);
        }

        // Create the event manager and the event template
        $eventManager = new EventManager();
        $event = new Event();
        $event->setArguments([
            'request' => $this->request,
            'response' => $this->response,
            'database' => $database,
            'storage' => $storage,
            'config' => $this->config,
            'manager' => $eventManager,
            'accessControl' => $accessControl,
            'transformationManager' => $transformationManager,
            'inputLoaderManager' => $inputLoaderManager,
            'outputConverterManager' => $outputConverterManager,
        ]);
        $eventManager->setEventTemplate($event);

        // A date formatter helper
        $dateFormatter = new Helpers\DateFormatter();

        // Response formatters
        $formatters = [
            'json' => new Formatter\JSON($dateFormatter),
        ];
        $contentNegotiation = new Http\ContentNegotiation();

        // Collect event listener data
        $eventListeners = [
            // Resources
            'Imbo\Resource\Index',
            'Imbo\Resource\Status',
            'Imbo\Resource\Stats',
            'Imbo\Resource\GlobalShortUrl',
            'Imbo\Resource\ShortUrls',
            'Imbo\Resource\ShortUrl',
            'Imbo\Resource\User',
            'Imbo\Resource\GlobalImages',
            'Imbo\Resource\Images',
            'Imbo\Resource\Image',
            'Imbo\Resource\Metadata',
            'Imbo\Resource\Groups',
            'Imbo\Resource\Group',
            'Imbo\Resource\Keys',
            'Imbo\Resource\Key',
            'Imbo\Resource\AccessRules',
            'Imbo\Resource\AccessRule',
            'Imbo\Http\Response\ResponseFormatter' => [
                'formatters' => $formatters,
                'contentNegotiation' => $contentNegotiation,
            ],
            'Imbo\EventListener\DatabaseOperations',
            'Imbo\EventListener\StorageOperations',
            'Imbo\Image\ImagePreparation',
            'Imbo\EventListener\ResponseSender',
            'Imbo\EventListener\ResponseETag',
            'Imbo\EventListener\HttpCache',
            'Imbo\Image\TransformationManager' => $transformationManager
        ];

        foreach ($eventListeners as $listener => $params) {
            $name = $listener;
            if (is_string($params)) {
                $listener = $params;
                $params = [];
                $name = $listener;
            } else if ($params instanceof ListenerInterface) {
                $listener = $params;
                $params = [];
                $name = get_class($listener);
            }

            $eventManager->addEventHandler($name, $listener, $params)
                         ->addCallbacks($name, $listener::getSubscribedEvents());
        }

        // Event listener initializers
        foreach ($this->config['eventListenerInitializers'] as $name => $initializer) {
            if (!$initializer) {
                // The initializer has been disabled via config
                continue;
            }

            if (is_string($initializer)) {
                // The initializer has been specified as a string, representing a class name. Create
                // an instance
                $initializer = new $initializer();
            }

            if (!($initializer instanceof InitializerInterface)) {
                throw new InvalidArgumentException('Invalid event listener initializer: ' . $name, 500);
            }

            $eventManager->addInitializer($initializer);
            $transformationManager->addInitializer($initializer);
        }

        // Listeners from configuration
        foreach ($this->config['eventListeners'] as $name => $definition) {
            if (!$definition) {
                // This occurs when a user disables a default event listener
                continue;
            }

            if (is_string($definition)) {
                // Class name
                $eventManager->addEventHandler($name, $definition)
                             ->addCallbacks($name, $definition::getSubscribedEvents());
                continue;
            }

            if (is_callable($definition) && !($definition instanceof ListenerInterface)) {
                // Callable piece of code which is not an implementation of the listener interface
                $definition = $definition($this->request, $this->response);
            }

            if ($definition instanceof ListenerInterface) {
                $eventManager->addEventHandler($name, $definition)
                             ->addCallbacks($name, $definition::getSubscribedEvents());
                continue;
            }

            if (is_array($definition) && !empty($definition['listener'])) {
                $listener = $definition['listener'];
                $params = is_string($listener) && isset($definition['params']) ? $definition['params'] : [];
                $users = isset($definition['users']) ? $definition['users'] : [];

                if (is_callable($listener) && !($listener instanceof ListenerInterface)) {
                    $listener = $listener($this->request, $this->response);
                }

                if (!is_string($listener) && !($listener instanceof ListenerInterface)) {
                    throw new InvalidArgumentException('Invalid event listener definition', 500);
                }

                $eventManager->addEventHandler($name, $listener, $params)
                             ->addCallbacks($name, $listener::getSubscribedEvents(), $users);
            } else if (is_array($definition) && !empty($definition['callback']) && !empty($definition['events'])) {
                $priority = 0;
                $events = [];
                $users = [];

                if (isset($definition['priority'])) {
                    $priority = (int) $definition['priority'];
                }

                if (isset($definition['users'])) {
                    $users = $definition['users'];
                }

                foreach ($definition['events'] as $event => $p) {
                    if (is_int($event)) {
                        $event = $p;
                        $p = $priority;
                    }

                    $events[$event] = $p;
                }

                $eventManager->addEventHandler($name, $definition['callback'])
                             ->addCallbacks($name, $events, $users);
            } else {
                throw new InvalidArgumentException('Invalid event listener definition', 500);
            }
        }

        // Custom resources
        foreach ($this->config['resources'] as $name => $resource) {
            if (is_callable($resource)) {
                $resource = $resource($this->request, $this->response);
            }

            $eventManager->addEventHandler($name, $resource)
                         ->addCallbacks($name, $resource::getSubscribedEvents());
        }

        $eventManager->trigger('imbo.initialized');

        try {
            // Route the request
            $router->route($this->request);

            $eventManager->trigger('route.match');

            // Create the resource
            $routeName = (string) $this->request->getRoute();

            if (isset($this->config['resources'][$routeName])) {
                $resource = $this->config['resources'][$routeName];

                if (is_callable($resource)) {
                    $resource = $resource($this->request, $this->response);
                }

                if (is_string($resource)) {
                    $resource = new $resource();
                }

                if (!$resource instanceof ResourceInterface) {
                    throw new InvalidArgumentException('Invalid resource class for route: ' . $routeName, 500);
                }
            } else {
                $className = 'Imbo\Resource\\' . ucfirst($routeName);
                $resource = new $className();
            }

            // Inform the user agent of which methods are allowed against this resource
            $this->response->headers->set('Allow', $resource->getAllowedMethods(), false);

            $methodName = strtolower($this->request->getMethod());

            // Generate the event name based on the accessed resource and the HTTP method
            $eventName = $routeName . '.' . $methodName;

            if (!$eventManager->hasListenersForEvent($eventName)) {
                throw new RuntimeException('Method not allowed', 405);
            }

            $eventManager->trigger($eventName)
                         ->trigger('response.negotiate');
        } catch (Exception $exception) {
            $negotiated = false;
            $error = Error::createFromException($exception, $this->request);
            $this->response->setError($error);

            // If the error is not from the previous attempt at doing content negotiation, force
            // another round since the model has changed into an error model.
            if ($exception->getCode() !== 406) {
                try {
                    $eventManager->trigger('response.negotiate');
                    $negotiated = true;
                } catch (Exception $exception) {
                    // The client does not accept any of the content types. Generate a new error
                    $error = Error::createFromException($exception, $this->request);
                    $this->response->setError($error);
                }
            }

            // Try to negotiate in a non-strict manner if the response format still has not been
            // chosen
            if (!$negotiated) {
                $eventManager->trigger('response.negotiate', [
                    'noStrict' => true,
                ]);
            }
        }

        // Send the response
        $eventManager->trigger('response.send');
    }
}
