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
    Imbo\Http\Request\RequestInterface,
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
     * Parameters for the listener
     *
     * @var array
     */
    private $params = array(
        /**
         * Use this parameter to enforce the access token listener for only some of the image
         * transformations (if the request is against an image). Each transformation must be
         * specified using the short names of the transformations (the name used in the query to
         * trigger the transformation).
         *
         * 'transformations' => array(
         *     'whitelist' => array(
         *         'border',
         *         'convert',
         *      ),
         * )
         *
         * Use the 'whitelist' for making the listener skip the access token check for some
         * transformations, and the 'blacklist' key for the opposite:
         *
         * 'whitelist' => array('convert') means that the access token
         * will *not* be enforced for the Convert transformation, but for all others.
         *
         * 'blacklist' => array('convert') means that the access token
         * will be enforced *only* when the Convert transformation is in effect.
         *
         * If both 'whitelist' and 'blacklist' are specified all transformations will require an
         * access token unless included in the 'whitelist'.
         */
        'transformations' => array(
            'whitelist' => array(),
            'blacklist' => array(),
        ),
    );

    /**
     * Class constructor
     *
     * @param array $params Parameters for the listener
     */
    public function __construct(array $params = array()) {
        if ($params) {
            $this->params = array_replace_recursive($this->params, $params);
        }
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function invoke(EventInterface $event) {
        $request = $event->getContainer()->get('request');
        $query = $request->getQuery();
        $eventName = $event->getName();

        if (($eventName === 'image.get.pre' || $eventName === 'image.head.pre') && $this->isWhitelisted($request)) {
            // All transformations in the request are whitelisted. Skip the access token check
            return;
        }

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

    /**
     * Check if the request is whitelisted
     *
     * @param RequestInterface $request The request instance
     * @return boolean
     */
    private function isWhitelisted(RequestInterface $request) {
        $filter = $this->params['transformations'];

        if (empty($filter['whitelist']) && empty($filter['blacklist'])) {
            return false;
        }

        $whitelist = array_flip($filter['whitelist']);
        $blacklist = array_flip($filter['blacklist']);
        $blacklisted = false;

        $transformations = array();

        foreach ($request->getTransformations() as $transformation) {
            $name = $transformation->getName();
            $flag = false;

            if (isset($blacklist[$name])) {
                $blacklisted = true;
                break;
            }

            if (isset($whitelist[$name]) || (empty($whitelist) && !empty($blacklist))) {
                $flag = true;
            }

            $transformations[$name] = $flag;
        }

        if ($blacklisted) {
            // Some of the transformations in the chain are blacklisted
            return false;
        }

        return count($transformations) === count(array_filter($transformations));
    }
}
