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
 * @package Imbo
 * @subpackage Server
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo;

use Imbo\Database\DatabaseInterface,
    Imbo\Storage\StorageInterface,
    Imbo\EventListener\ListenerInterface,
    Imbo\EventListener\PublicKeyAwareListenerInterface,
    Imbo\Http\Request\Request,
    Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\Response,
    Imbo\Http\Response\ResponseWriter,
    Imbo\Image\Image,
    Imbo\EventManager\EventManager,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Exception\HaltApplication,
    Imbo\Image\Transformation,
    DateTime;

// Fetch the configuration
$configPath = __DIR__ . '/../config/config.php';
$config = require $configPath;

// Create the container and inject some properties
$container = new Container();
$container->config = $config;
$container->request = new Request($_GET, $_POST, $_SERVER);
$container->response = new Response();
$container->response->getHeaders()->set('X-Imbo-Version', Version::getVersionNumber());
$container->image = new Image();
$container->application = new Application();

// Resources
$container->metadataResource = $container->shared(function(Container $container) {
    return new Resource\Metadata();
});
$container->imagesResource = $container->shared(function(Container $container) {
    return new Resource\Images();
});
$container->userResource = $container->shared(function(Container $container) {
    return new Resource\User();
});
$container->statusResource = $container->shared(function(Container $container) {
    return new Resource\Status();
});
$container->imageResource = $container->shared(function(Container $container) {
    $imageResource = new Resource\Image($container->image);

    foreach ($container->config['transformations'] as $name => $callback) {
        $imageResource->registerTransformationHandler($name, $callback);
    }

    return $imageResource;
});

// Router
$container->router = $container->shared(function(Container $container) {
    return new Router($container);
});

// Create the database entry
$container->database = $container->shared(function(Container $container) {
    $config = $container->config['database'];
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

// Create the storage entry
$container->storage = $container->shared(function(Container $container) {
    $config = $container->config['storage'];
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

// Create the event manager and add listeners
$container->eventManager = $container->shared(function(Container $container) {
    $manager = new EventManager(
        $container->request, $container->response, $container->database, $container->storage, $container->config
    );

    // Register internal event listeners
    $manager->attachListener($container->statusResource, 50)
            ->attachListener($container->userResource, 50)
            ->attachListener($container->imagesResource, 50)
            ->attachListener($container->imageResource, 50)
            ->attachListener($container->metadataResource, 50)
            ->attachListener($container->response, 50)
            ->attachListener($container->router, 50)
            ->attachListener($container->application, 50);

    // Register event listeners from the configuration
    $listeners = $container->config['eventListeners'];

    foreach ($listeners as $definition) {
        if ($definition instanceof ListenerInterface) {
            $manager->attachListener($definition);
            continue;
        }

        if (!is_array($definition) || empty($definition['listener'])) {
            throw new InvalidArgumentException('Missing listener definition', 500);
        }

        $listener = $definition['listener'];

        if ($listener instanceof ListenerInterface) {
            if (
                $listener instanceof PublicKeyAwareListenerInterface &&
                !empty($definition['publicKeys']) &&
                is_array($definition['publicKeys'])
            ) {
                $listener->setPublicKeys($definition['publicKeys']);
            }

            $priority = isset($definition['priority']) ? $definition['priority'] : 1;

            $manager->attachListener($listener, $priority);
        } else if (is_callable($listener) && !empty($definition['events']) && is_array($definition['events'])) {
            $manager->attach($definition['events'], $listener);
        } else {
            throw new InvalidArgumentException('Invalid listener', 500);
        }
    }

    return $manager;
});

try {
    $container->eventManager->trigger('route')
                            ->trigger('run', array('container' => $container));
} catch (HaltApplication $exception) {
    // Special type of exception that the event manager can throw if an event listener wants to
    // halt the execution of Imbo. No special action should be taken, simply send the response
    // as usual
    unset($exception);
} catch (Exception $exception) {
    // An error has occured. Create an error and send the response
    $container->response->createError($exception, $container->request);
}

$container->eventManager->trigger('response.send');
