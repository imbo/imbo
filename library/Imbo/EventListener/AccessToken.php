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
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\Http\Response\ResponseInterface,
    Imbo\Exception\RuntimeException;

/**
 * Access token event listener
 *
 * This event listener will listen to all GET and HEAD requests and make sure that they include a
 * valid access token. The official PHP-based imbo client (https://github.com/imbo/imboclient-php)
 * appends this token to all such requests by default. If the access token is missing or invalid
 * the event listener will throw an exception resulting in a HTTP response with 400 Bad Request.
 *
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class AccessToken extends Listener implements ListenerInterface {
    /**
     * @see Imbo\EventListener\ListenerInterface::getEvents()
     */
    public function getEvents() {
        return array(
            'user.get.pre',
            'images.get.pre',
            'image.get.pre',
            'metadata.get.pre',

            'user.head.pre',
            'images.head.pre',
            'image.head.pre',
            'metadata.head.pre',
        );
    }

    /**
     * @see Imbo\EventListener\ListenerInterface::invoke()
     */
    public function invoke(EventInterface $event) {
        $request = $event->getRequest();
        $query = $request->getQuery();

        if (!$query->has('accessToken')) {
            throw new RuntimeException('Missing access token', 400);
        }

        $token = $query->get('accessToken');
        $url = $request->getUrl();

        // Remove the access token from the query string as it's not used to generate the HMAC
        $url = trim(preg_replace('/(\?|&)accessToken=' . $token . '&?/', '\\1', $url), '&?');

        $correctToken = hash_hmac('sha256', $url, $request->getPrivateKey());

        if ($correctToken !== $token) {
            throw new RuntimeException('Incorrect access token', 400);
        }
    }
}
