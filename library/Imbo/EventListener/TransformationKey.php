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
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\Http\Response\ResponseInterface,
    Imbo\Exception;

/**
 * Transformation key
 *
 * This event listener will listen to all GET requests for images and make sure that requests that
 * will end up doing a transformation to an image includes a correct transformation key. The
 * official PHP-based imbo client (https://github.com/christeredvartsen/imboclient-php) does this
 * by default.
 *
 * This event listener listens for GET and HEAD requests for image resources.
 *
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class TransformationKey implements ListenerInterface {
    /**
     * @see Imbo\EventListener\ListenerInterface::getEvents()
     */
    public function getEvents() {
        return array(
            'image.get.pre',
            'image.head.pre',
        );
    }

    /**
     * @see Imbo\EventListener\ListenerInterface::invoke()
     */
    public function invoke(EventInterface $event) {
        $request = $event->getRequest();
        $params = $request->getQuery();

        // Fetch a possible extension
        $extension = $request->getImageExtension();

        if (!$extension && !$params->has('t')) {
            // No custom extension and no transformations. Simply return as we don't have to verify
            // any transformation key
            return;
        }

        if (!$params->has('tk')) {
            // We have a custom extension and/or one or more transformations, but no key
            throw new Exception('Missing transformation key', 400);
        }

        // We have a key. Lets see if it's correct
        $key = $params->get('tk');

        // Fetch transformations
        $transformations = $params->get('t');

        // Initialize data used for the HMAC
        $data = $request->getPublicKey() . '|' . $request->getImageIdentifier();

        if ($extension) {
            // Append custom extension
            $data .= '.' . $extension;
        }

        if ($transformations) {
            // We have transformations. Build the query string (in the same was as the client does
            // it)
            $query = null;
            $query = array_reduce($transformations, function($query, $element) {
                return $query . 't%5B%5D=' . urlencode($element) . '&';
            }, $query);

            $data .= '|' . rtrim($query, '&');
        }

        // Compute the key using the private key of the current user
        $actualKey = hash_hmac('md5', $data, $request->getPrivateKey());

        if ($key !== $actualKey) {
            // Key from the request is not correct
            throw new Exception('Invalid transformation key', 400);
        }
    }
}
