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

use Imbo\EventManager\EventInterface,
    Imbo\EventListener\ListenerInterface,
    Imbo\Exception\RuntimeException,
    Imbo\Exception;

/**
 * Imbo application
 *
 * @package Core
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Application implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public function getEvents() {
        return array(
            'run',
        );
    }

    /**
     * Run the application
     *
     * @param EventInterface $event An event instance
     */
    public function onRun(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $manager = $event->getManager();
        $params = $event->getParams();
        $config = $event->getConfig();

        $container = $params['container'];

        $resource = $request->getResource();
        $entry = $resource . 'Resource';

        if (!$container->has($entry)) {
            throw new RuntimeException('Unknown Resource', 500);
        }

        $resource = $container->$entry;

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

        if (!$manager->hasListenersForEvent($eventName)) {
            throw new RuntimeException('Method not allowed', 405);
        }

        $manager->trigger($eventName);
    }
}
