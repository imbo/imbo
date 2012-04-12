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
    Imbo\Http\Request\Request,
    Imbo\Http\Response\Response,
    Imbo\Http\Response\ResponseWriter,
    Imbo\Image\Image,
    Imbo\EventManager\EventManager,
    Imbo\Exception\InvalidArgumentException;

// Fetch the configuration
$configPath = __DIR__ . '/../config/config.php';
$config = require $configPath;

// Create a new DIC and inject the configuration
$container = new Container();
$container->config = $config;

// Image object that can be used by the image resource
$container->image = $container->shared(function(Container $container) {
    return new Image();
});

// Resources
$container->metadataResource = $container->shared(function(Container $container) {
    return new Resource\Metadata();
});
$container->imagesResource = $container->shared(function(Container $container) {
    return new Resource\Images();
});
$container->imageResource = $container->shared(function(Container $container) {
    return new Resource\Image($container->image);
});
$container->userResource = $container->shared(function(Container $container) {
    return new Resource\User();
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

// Create request and response objects
$container->request = new Request($_GET, $_POST, $_SERVER);
$container->response = new Response();

// Event manager
$container->eventManager = $container->shared(function(Container $container) {
    $listeners = $container->config['eventListeners'];
    $manager = new EventManager($container->request, $container->response, $container->image);

    foreach ($listeners as $def) {
        if (empty($def['listener'])) {
            throw new InvalidArgumentException('Missing listener definition', 500);
        }

        $listener = $def['listener'];

        if ($listener instanceof ListenerInterface) {
            if (!empty($def['publicKeys']) && is_array($def['publicKeys'])) {
                $listener->setPublicKeys($def['publicKeys']);
            }

            $manager->attachListener($listener);
        } else if (is_callable($listener) && !empty($def['events']) && is_array($def['events'])) {
            $manager->attach($def['events'], $listener);
        } else {
            throw new InvalidArgumentException('Invalid listener', 500);
        }
    }

    return $manager;
});

// Fetch some entries from the container
$request = $container->request;
$response = $container->response;

// Add a version header
$response->getHeaders()->set('X-Imbo-Version', Version::getVersionNumber());

// Create the front controller and handle the request
$frontController = new FrontController($container);

try {
    $frontController->handle($request, $response);
} catch (Exception $exception) {
    $response->setStatusCode($exception->getCode());
    $internalCode = $exception->getImboErrorCode();

    if ($internalCode === null) {
        $internalCode = Exception::ERR_UNSPECIFIED;
    }

    $data = array(
        'error' => array(
            'code'          => $exception->getCode(),
            'message'       => $exception->getMessage(),
            'timestamp'     => gmdate('Y-m-d\TH:i:s\Z'),
            'imboErrorCode' => $internalCode,
        ),
    );

    // Fetch the real image identifier (PUT only) or the one from the URL (if present)
    if (($identifier = $request->getRealImageIdentifier()) || ($identifier = $request->getImageIdentifier())) {
        $data['imageIdentifier'] = $identifier;
    }

    $responseWriter = new ResponseWriter();
    $responseWriter->write($data, $request, $response);
}

// Send the response to the client
$response->send();
