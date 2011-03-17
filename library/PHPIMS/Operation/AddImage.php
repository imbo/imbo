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
 * @subpackage Operations
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

/**
 * Add image operation
 *
 * This operation will add a new image to the server.
 *
 * @package PHPIMS
 * @subpackage Operations
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_Operation_AddImage extends PHPIMS_Operation_Abstract {
    /**
     * Internal plugins
     *
     * @var array
     */
    protected $internalPlugins = array(
        'PHPIMS_Operation_Plugin_PrepareImage' => array(),
    );

    /**
     * Execute the operation
     *
     * Operations must implement this method and return a PHPIMS_Server_Response object to return
     * to the client.
     *
     * @return PHPIMS_Operation_AddImage
     * @throws PHPIMS_Operation_Exception
     */
    public function exec() {
        $image = $this->getImage();

        try {
            $this->getDatabase()->insertNewImage($image);
        } catch (PHPIMS_Database_Exception $e) {
            throw new PHPIMS_Operation_Exception('Unable to add image to the database', 500, $e);
        }

        try {
            $this->getStorage()->store($_FILES['file']['tmp_name'], $image);
        } catch (PHPIMS_Storage_Exception $e) {
            throw new PHPIMS_Operation_Exception('Unable to store the image', 500, $e);
        }

        $location = $_SERVER['HTTP_HOST'] . '/' . $image->getId();

        $this->getResponse()->setCode(201)
                            ->addHeader('Location: http://' . $location)
                            ->setBody(array(
                                'id' => $image->getId(),
                            ));

        return $this;
    }
}