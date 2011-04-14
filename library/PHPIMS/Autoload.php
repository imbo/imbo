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
 * @subpackage Client
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
 
namespace PHPIMS;

/**
 * Autoloader used by PHPIMS
 *
 * @package PHPIMS
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class Autoload {
    /**
     * PHPIMS classes
     *
     * @var array
     */
    static public $classes = array(
        'phpims\\autoload' => '/Autoload.php',
        'phpims\\client' => '/Client.php',
        'phpims\\client\\driver' => '/Client/Driver.php',
        'phpims\\client\\driver\\curl' => '/Client/Driver/Curl.php',
        'phpims\\client\\driver\\exception' => '/Client/Driver/Exception.php',
        'phpims\\client\\driverinterface' => '/Client/DriverInterface.php',
        'phpims\\client\\exception' => '/Client/Exception.php',
        'phpims\\client\\response' => '/Client/Response.php',
        'phpims\\database\\driver' => '/Database/Driver.php',
        'phpims\\database\\driver\\mongodb' => '/Database/Driver/MongoDB.php',
        'phpims\\database\\driverinterface' => '/Database/DriverInterface.php',
        'phpims\\database\\exception' => '/Database/Exception.php',
        'phpims\\exception' => '/Exception.php',
        'phpims\\frontcontroller' => '/FrontController.php',
        'phpims\\image' => '/Image.php',
        'phpims\\operation' => '/Operation.php',
        'phpims\\operationinterface' => '/OperationInterface.php',
        'phpims\\server\\response' => '/Server/Response.php',
        'phpims\\storage\\driver' => '/Storage/Driver.php',
        'phpims\\storage\\driver\\filesystem' => '/Storage/Driver/Filesystem.php',
        'phpims\\storage\\driverinterface' => '/Storage/DriverInterface.php',
        'phpims\\storage\\exception' => '/Storage/Exception.php',
        'phpims_operation_addimage' => '/Operation/AddImage.php',
        'phpims_operation_deleteimage' => '/Operation/DeleteImage.php',
        'phpims_operation_deletemetadata' => '/Operation/DeleteMetadata.php',
        'phpims_operation_editmetadata' => '/Operation/EditMetadata.php',
        'phpims_operation_exception' => '/Operation/Exception.php',
        'phpims_operation_getimage' => '/Operation/GetImage.php',
        'phpims_operation_getmetadata' => '/Operation/GetMetadata.php',
        'phpims_operation_plugin' => '/Operation/Plugin.php',
        'phpims_operation_plugin_authplugin' => '/Operation/Plugin/AuthPlugin.php',
        'phpims_operation_plugin_exception' => '/Operation/Plugin/Exception.php',
        'phpims_operation_plugin_identifyimageplugin' => '/Operation/Plugin/IdentifyImagePlugin.php',
        'phpims_operation_plugin_manipulateimageplugin' => '/Operation/Plugin/ManipulateImagePlugin.php',
        'phpims_operation_plugin_manipulateimageplugin_transformation_border' => '/Operation/Plugin/ManipulateImagePlugin/Transformation/Border.php',
        'phpims_operation_plugin_manipulateimageplugin_transformation_crop' => '/Operation/Plugin/ManipulateImagePlugin/Transformation/Crop.php',
        'phpims_operation_plugin_manipulateimageplugin_transformation_resize' => '/Operation/Plugin/ManipulateImagePlugin/Transformation/Resize.php',
        'phpims_operation_plugin_manipulateimageplugin_transformation_rotate' => '/Operation/Plugin/ManipulateImagePlugin/Transformation/Rotate.php',
        'phpims_operation_plugin_manipulateimageplugin_transformationinterface' => '/Operation/Plugin/ManipulateImagePlugin/TransformationInterface.php',
        'phpims_operation_plugin_prepareimageplugin' => '/Operation/Plugin/PrepareImagePlugin.php'
    );

    /**
     * Load a class
     *
     * @param string $class The name of the class to load
     */
    static function load($class) {
        $className = strtolower($class);

        if (isset(static::$classes[$className])) {
            require __DIR__ . static::$classes[$className];
        }
    }
}

spl_autoload_register('Autoload::load');