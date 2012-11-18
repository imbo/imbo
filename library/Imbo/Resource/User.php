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
 * @package Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Resource;

use Imbo\Http\Request\RequestInterface,
    Imbo\EventManager\EventInterface;

/**
 * User resource
 *
 * @package Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class User extends Resource implements UserInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return array(
            RequestInterface::METHOD_GET,
            RequestInterface::METHOD_HEAD,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getEvents() {
        return array(
            'user.get',
            'user.head',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function onUserGet(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $database = $event->getDatabase();

        $publicKey = $request->getPublicKey();

        // Fetch header containers
        $responseHeaders = $response->getHeaders();

        // Fetch the number of images this user has in the database
        $numImages = $database->getNumImages($publicKey);

        // Fetch the last modfified timestamp for the current user
        $lastModified = $this->formatDate($database->getLastModified($publicKey));

        // Generate ETag based on the last modification date and add to the response headers
        $etag = '"' . md5($lastModified) . '"';
        $responseHeaders->set('ETag', $etag);
        $responseHeaders->set('Last-Modified', $lastModified);

        $response->setBody(array(
            'publicKey'    => $publicKey,
            'numImages'    => $numImages,
            'lastModified' => $lastModified,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function onUserHead(EventInterface $event) {
        $this->onUserGet($event);

        // Remove body from the response, but keep everything else
        $event->getResponse()->setBody(null);
    }
}
