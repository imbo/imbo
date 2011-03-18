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

/**
 * Autoloader used by PHPIMS
 *
 * @package PHPIMS
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_Autoload {
    /**
     * PHPIMS  classes
     *
     * @var array
     */
    static public $classes = array(
        'phpims_autoload' => '/Autoload.php',
        'phpims_client' => '/Client.php',
        'phpims_client_driver_abstract' => '/Client/Driver/Abstract.php',
        'phpims_client_driver_curl' => '/Client/Driver/Curl.php',
        'phpims_client_driver_exception' => '/Client/Driver/Exception.php',
        'phpims_client_driver_interface' => '/Client/Driver/Interface.php',
        'phpims_client_exception' => '/Client/Exception.php',
        'phpims_client_response' => '/Client/Response.php',
        'phpims_database_driver_abstract' => '/Database/Driver/Abstract.php',
        'phpims_database_driver_interface' => '/Database/Driver/Interface.php',
        'phpims_database_driver_mongodb' => '/Database/Driver/MongoDB.php',
        'phpims_database_exception' => '/Database/Exception.php',
        'phpims_exception' => '/Exception.php',
        'phpims_frontcontroller' => '/FrontController.php',
        'phpims_image' => '/Image.php',
        'phpims_operation' => '/Operation.php',
        'phpims_operation_abstract' => '/Operation/Abstract.php',
        'phpims_operation_addimage' => '/Operation/AddImage.php',
        'phpims_operation_deleteimage' => '/Operation/DeleteImage.php',
        'phpims_operation_deletemetadata' => '/Operation/DeleteMetadata.php',
        'phpims_operation_editmetadata' => '/Operation/EditMetadata.php',
        'phpims_operation_exception' => '/Operation/Exception.php',
        'phpims_operation_getimage' => '/Operation/GetImage.php',
        'phpims_operation_getmetadata' => '/Operation/GetMetadata.php',
        'phpims_operation_plugin_abstract' => '/Operation/Plugin/Abstract.php',
        'phpims_operation_plugin_exception' => '/Operation/Plugin/Exception.php',
        'phpims_operation_plugin_prepareimage' => '/Operation/Plugin/PrepareImage.php',
        'phpims_server_response' => '/Server/Response.php',
        'phpims_storage_driver_abstract' => '/Storage/Driver/Abstract.php',
        'phpims_storage_driver_filesystem' => '/Storage/Driver/Filesystem.php',
        'phpims_storage_driver_interface' => '/Storage/Driver/Interface.php',
        'phpims_storage_exception' => '/Storage/Exception.php'
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

spl_autoload_register('PHPIMS_Autoload::load');