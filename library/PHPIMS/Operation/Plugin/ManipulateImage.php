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
 * @subpackage OperationPlugin
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Operation\Plugin;

use PHPIMS\Operation\PluginInterface;
use PHPIMS\Operation;

use PHPIMS\Image\Transformation\Border;
use PHPIMS\Image\Transformation\Crop;
use PHPIMS\Image\Transformation\FlipHorizontally;
use PHPIMS\Image\Transformation\FlipVertically;
use PHPIMS\Image\Transformation\Resize;
use PHPIMS\Image\Transformation\Rotate;
use PHPIMS\Image\Transformation\Thumbnail;

use \Imagine\Imagick\Imagine as Imagine;
use \Imagine\ImageInterface as ImagineImage;

/**
 * Manipulate image plugin
 *
 * This plugin enables image manipulation using query parameters. Users can specify as many
 * transformations they want. Transformations will be applied in the order they are given. Each
 * transformation is added using the following format:
 *
 * <pre>t[]=transformation:param=value[,param=value[, ... ]]</pre>
 *
 * Valid values for <transformation> and their respective parameters are:
 *
 * <ul>
 *   <li>
 *     <pre>resize</pre>
 *     This transformation will resize the image. Two parameters are supported and at least one of them must be supplied to apply this transformation:
 *     <ul>
 *       <li><pre>(int) width</pre>The width of the resulting image in pixels. If not specified the width will be calculated using the same ratio as the original image.</li>
 *       <li><pre>(int) height</pre>The height of the resulting image in pixels. If not specified the height will be calculated using the same ratio as the original image.</li>
 *     </ul>
 *   </li>
 *   <li>
 *     <pre>crop</pre>
 *     This transformation will crop the image. All four arguments are required.
 *     <ul>
 *       <li><pre>(int) x</pre>The X coordinate of the cropped region's top left corner</li>
 *       <li><pre>(int) y</pre>The Y coordinate of the cropped region's top left corner</li>
 *       <li><pre>(int) width</pre>The width of the crop</li>
 *       <li><pre>(int) height</pre>The height of the crop</li>
 *     </ul>
 *   </li>
 *   <li>
 *     <pre>rotate</pre>
 *     Use this transformation to rotate the image.
 *     <ul>
 *       <li><pre>(int) angle</pre>The number of degrees to rotate the image</li>
 *       <li><pre>(string) bg</pre>Background color in hexadecimal. Defaults to "000000"</li>
 *     </ul>
 *   </li>
 *   <li>
 *     <pre>border</pre>
 *     If you want to add a border around the image, use this transformation.
 *     <ul>
 *       <li><pre>(string) color</pre>Color in hexadecimal. Defaults to "000000" (also supports short variants, for instance '000' or 'f00')</li>
 *       <li><pre>(int) width</pre>Width of the border on the left and right sides of the image. Defaults to 1</li>
 *       <li><pre>(int) height</pre>Height of the border on the top and bottoms sides of the image. Defaults to 1</li>
 *     </ul>
 *   </li>
 * </ul>
 *
 * Examples of transformations:
 *
 * - <pre>?t[]=border&t[]=resize:width=100,height=50</pre>
 * - <pre>?t[]=crop:x=10,y=20,width=100,height=50&t[]=rotate:angle=45</pre>
 *
 * @package PHPIMS
 * @subpackage OperationPlugin
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class ManipulateImage implements PluginInterface {
    /**
     * Events this plugin will hook into
     *
     * @var array
     */
    static public $events = array(
        'getImagePostExec' => 101,
    );

    /**
     * @see PHPIMS\Operation\PluginInterface::exec()
     */
    public function exec(Operation $operation) {
        if (isset($_GET['t']) && is_array($_GET['t'])) {
            $originalImage = $operation->getImage();

            // Load the image into imagine
            $imagine = new Imagine();
            $image = $imagine->load($originalImage->getBlob());

            foreach ($_GET['t'] as $transformation) {
                // See if the transformation has any parameters
                $pos = strpos($transformation, ':');
                $urlParams = '';

                if ($pos === false) {
                    // No params exist
                    $name = $transformation;
                } else {
                    list($name, $urlParams) = explode(':', $transformation, 2);
                }

                // Initialize params for the transformation
                $params = array();

                // See if we have more than one parameter
                if (strpos($urlParams, ',') !== false) {
                    $urlParams = explode(',', $urlParams);
                } else {
                    $urlParams = array($urlParams);
                }

                foreach ($urlParams as $param) {
                    $pos = strpos($param, '=');

                    if ($pos !== false) {
                        $params[substr($param, 0, $pos)] = substr($param, $pos + 1);
                    }
                }

                $p = function($key) use ($params) {
                    return isset($params[$key]) ? $params[$key] : null;
                };

                switch ($name) {
                    case 'border':
                        $transformation = new Border($p('color'), $p('width'), $p('height'));
                        break;
                    case 'crop':
                        $transformation = new Crop($p('x'), $p('y'), $p('width'), $p('height'));
                        break;
                    case 'flipHorizontally':
                        $transformation = new FlipHorizontally();
                        break;
                    case 'flipVertically':
                        $transformation = new FlipVertically();
                        break;
                    case 'resize':
                        $transformation = new Resize($p('width'), $p('height'));
                        break;
                    case 'rotate':
                        $transformation = new Rotate($p('angle'), $p('bg'));
                        break;
                    case 'thumbnail':
                        $transformation = new Thumbnail($p('width'), $p('height'), $p('fit'));
                        break;
                    default:
                        // Unsupported transformation
                        continue;
                }

                try {
                    $newImage = $transformation->applyToImage($image);

                    if ($newImage instanceof ImagineImage) {
                        $image = $newImage;
                    }
                } catch (\Imagine\Exception\Exception $e) {
                    trigger_error('Imagine failed with exception: ' . $e->getMessage(), E_USER_WARNING);
                }
            }

            $originalImage->setBlob((string) $image);
        }
    }
}