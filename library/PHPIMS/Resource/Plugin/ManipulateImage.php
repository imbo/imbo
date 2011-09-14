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
 * @subpackage Plugins
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Resource\Plugin;

use PHPIMS\Http\Request\RequestInterface;
use PHPIMS\Http\Response\ResponseInterface;
use PHPIMS\Database\DatabaseInterface;
use PHPIMS\Storage\StorageInterface;
use PHPIMS\Image\Transformation\Exception as TransformationException;
use PHPIMS\Image\ImageInterface;

/**
 * Manipulate image plugin
 *
 * This plugin enables image manipulation using query parameters. Users can specify as many
 * transformations they want. Transformations will be applied in the order they are given.
 *
 * @package PHPIMS
 * @subpackage Plugins
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class ManipulateImage implements PluginInterface {
    /**
     * Image property
     *
     * @var PHPIMS\Image\ImageInterface
     */
    private $image;

    /**
     * Class constructor
     *
     * @param PHPIMS\Image\ImageInterface $image Image instance
     */
    public function __construct(ImageInterface $image) {
        $this->image = $image;
    }

    /**
     * @see PHPIMS\Resource\Plugin\PluginInterface::exec()
     */
    public function exec(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $transformationChain = $request->getTransformations();

        try {
            $transformationChain->applyToImage($this->image);
        } catch (TransformationException $e) {
            throw new Exception('Transformation failed with message: ' . $e->getMessage(), 401, $e);
        }

        $response->setBody($this->image->getBlob());
    }
}
