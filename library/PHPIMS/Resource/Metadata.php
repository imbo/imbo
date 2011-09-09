<?php
/**
 * PHPIMS
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
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
 * @package PHPIMS
 * @subpackage Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Resource;

use PHPIMS\Request\RequestInterface;
use PHPIMS\Response\ResponseInterface;
use PHPIMS\Database\DatabaseInterface;
use PHPIMS\Storage\StorageInterface;
use PHPIMS\Resource\Plugin\PluginInterface;
use PHPIMS\Resource\Plugin;

/**
 * Metadata resource
 *
 * @package PHPIMS
 * @subpackage Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class Metadata extends Resource implements ResourceInterface {
    /**
     * Class constructor
     */
    public function __construct() {
        $auth = new Plugin\Auth();

        $this->registerPlugin(ResourceInterface::STATE_PRE, RequestInterface::METHOD_POST,   100, $auth)
             ->registerPlugin(ResourceInterface::STATE_PRE, RequestInterface::METHOD_DELETE, 100, $auth);
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::getAllowedMethods()
     */
    public function getAllowedMethods() {
        return array(
            RequestInterface::METHOD_GET,
            RequestInterface::METHOD_POST,
            RequestInterface::METHOD_DELETE,
        );
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::delete()
     */
    public function delete(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $database->deleteMetadata($request->getPublicKey(), $request->getImageIdentifier());
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::post()
     */
    public function post(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $imageIdentifier = $request->getImageIdentifier();
        $database->updateMetadata($request->getPublicKey(), $imageIdentifier, $request->getMetadata());
        $response->setCode(200)
                 ->setBody(array('imageIdentifier' => $imageIdentifier));
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::get()
     */
    public function get(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $data = $database->getMetadata($request->getPublicKey(), $request->getImageIdentifier());
        $response->setBody($data);
    }
}
