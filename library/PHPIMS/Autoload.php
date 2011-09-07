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
        'phpims\\client\\driver\\curl' => '/Client/Driver/Curl.php',
        'phpims\\client\\driver\\driverinterface' => '/Client/Driver/DriverInterface.php',
        'phpims\\client\\driver\\exception' => '/Client/Driver/Exception.php',
        'phpims\\client\\exception' => '/Client/Exception.php',
        'phpims\\client\\imageurl' => '/Client/ImageUrl.php',
        'phpims\\client\\response' => '/Client/Response.php',
        'phpims\\database\\databaseinterface' => '/Database/DatabaseInterface.php',
        'phpims\\database\\exception' => '/Database/Exception.php',
        'phpims\\database\\mongodb' => '/Database/MongoDB.php',
        'phpims\\exception' => '/Exception.php',
        'phpims\\frontcontroller' => '/FrontController.php',
        'phpims\\image\\image' => '/Image/Image.php',
        'phpims\\image\\imageinterface' => '/Image/ImageInterface.php',
        'phpims\\image\\transformation\\border' => '/Image/Transformation/Border.php',
        'phpims\\image\\transformation\\compress' => '/Image/Transformation/Compress.php',
        'phpims\\image\\transformation\\crop' => '/Image/Transformation/Crop.php',
        'phpims\\image\\transformation\\exception' => '/Image/Transformation/Exception.php',
        'phpims\\image\\transformation\\fliphorizontally' => '/Image/Transformation/FlipHorizontally.php',
        'phpims\\image\\transformation\\flipvertically' => '/Image/Transformation/FlipVertically.php',
        'phpims\\image\\transformation\\resize' => '/Image/Transformation/Resize.php',
        'phpims\\image\\transformation\\rotate' => '/Image/Transformation/Rotate.php',
        'phpims\\image\\transformation\\thumbnail' => '/Image/Transformation/Thumbnail.php',
        'phpims\\image\\transformation\\transformationinterface' => '/Image/Transformation/TransformationInterface.php',
        'phpims\\image\\transformationchain' => '/Image/TransformationChain.php',
        'phpims\\request\\exception' => '/Request/Exception.php',
        'phpims\\request\\request' => '/Request/Request.php',
        'phpims\\request\\requestinterface' => '/Request/RequestInterface.php',
        'phpims\\resource\\image' => '/Resource/Image.php',
        'phpims\\resource\\images' => '/Resource/Images.php',
        'phpims\\resource\\images\\query' => '/Resource/Images/Query.php',
        'phpims\\resource\\metadata' => '/Resource/Metadata.php',
        'phpims\\resource\\plugin\\auth' => '/Resource/Plugin/Auth.php',
        'phpims\\resource\\plugin\\exception' => '/Resource/Plugin/Exception.php',
        'phpims\\resource\\plugin\\identifyimage' => '/Resource/Plugin/IdentifyImage.php',
        'phpims\\resource\\plugin\\manipulateimage' => '/Resource/Plugin/ManipulateImage.php',
        'phpims\\resource\\plugin\\plugininterface' => '/Resource/Plugin/PluginInterface.php',
        'phpims\\resource\\plugin\\prepareimage' => '/Resource/Plugin/PrepareImage.php',
        'phpims\\resource\\resource' => '/Resource/Resource.php',
        'phpims\\resource\\resourceinterface' => '/Resource/ResourceInterface.php',
        'phpims\\response\\response' => '/Response/Response.php',
        'phpims\\response\\responseinterface' => '/Response/ResponseInterface.php',
        'phpims\\storage\\exception' => '/Storage/Exception.php',
        'phpims\\storage\\filesystem' => '/Storage/Filesystem.php',
        'phpims\\storage\\storageinterface' => '/Storage/StorageInterface.php'
    );

    /**
     * Load a class
     *
     * @param string $class The name of the class to load
     */
    static public function load($class) {
        $className = strtolower($class);

        if (isset(static::$classes[$className])) {
            require __DIR__ . static::$classes[$className];
        }
    }

    /**
     * Registers this instance as an autoloader
     *
     * @codeCoverageIgnore
     */
    public function register() {
        // Register the autoloader
        spl_autoload_register(array($this, 'load'));
    }
}
