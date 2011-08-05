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

use PHPIMS\Operation;
use PHPIMS\Image\Transformation;

/**
 * Manipulate image plugin
 *
 * This plugin enables image manipulation using query parameters. Users can specify as many
 * transformations they want. Transformations will be applied in the order they are given.
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
     * @see PHPIMS\Operation\Plugin\PluginInterface::exec()
     */
    public function exec(Operation $operation) {
        if (isset($_GET['t']) && is_array($_GET['t'])) {
            $image = $operation->getImage();

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
                        $transformation = new Transformation\Border($p('color'), $p('width'), $p('height'));
                        break;
                    case 'compress':
                        $transformation = new Transformation\Compress($p('quality'));
                        break;
                    case 'crop':
                        $transformation = new Transformation\Crop($p('x'), $p('y'), $p('width'), $p('height'));
                        break;
                    case 'flipHorizontally':
                        $transformation = new Transformation\FlipHorizontally();
                        break;
                    case 'flipVertically':
                        $transformation = new Transformation\FlipVertically();
                        break;
                    case 'resize':
                        $transformation = new Transformation\Resize($p('width'), $p('height'));
                        break;
                    case 'rotate':
                        $transformation = new Transformation\Rotate($p('angle'), $p('bg'));
                        break;
                    case 'thumbnail':
                        $transformation = new Transformation\Thumbnail($p('width'), $p('height'), $p('fit'));
                        break;
                    default:
                        // Unsupported transformation. Continue to the next transformation
                        continue 2;
                }

                try {
                    $transformation->applyToImage($image);
                } catch (Transformation\Exception $e) {
                    trigger_error('Transformation failed with exception: ' . $e->getMessage(), E_USER_WARNING);
                }
            }
        }
    }
}
