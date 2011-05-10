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
        'phpims\\client\\driver\\exception' => '/Client/Driver/Exception.php',
        'phpims\\client\\driverinterface' => '/Client/DriverInterface.php',
        'phpims\\client\\exception' => '/Client/Exception.php',
        'phpims\\client\\imageurl' => '/Client/ImageUrl.php',
        'phpims\\client\\response' => '/Client/Response.php',
        'phpims\\database\\driver\\mongodb' => '/Database/Driver/MongoDB.php',
        'phpims\\database\\driver\\mysql' => '/Database/Driver/MySQL.php',
        'phpims\\database\\driverinterface' => '/Database/DriverInterface.php',
        'phpims\\database\\exception' => '/Database/Exception.php',
        'phpims\\exception' => '/Exception.php',
        'phpims\\frontcontroller' => '/FrontController.php',
        'phpims\\image' => '/Image.php',
        'phpims\\image\\transformation\\border' => '/Image/Transformation/Border.php',
        'phpims\\image\\transformation\\crop' => '/Image/Transformation/Crop.php',
        'phpims\\image\\transformation\\fliphorizontally' => '/Image/Transformation/FlipHorizontally.php',
        'phpims\\image\\transformation\\flipvertically' => '/Image/Transformation/FlipVertically.php',
        'phpims\\image\\transformation\\resize' => '/Image/Transformation/Resize.php',
        'phpims\\image\\transformation\\rotate' => '/Image/Transformation/Rotate.php',
        'phpims\\image\\transformation\\thumbnail' => '/Image/Transformation/Thumbnail.php',
        'phpims\\image\\transformationchain' => '/Image/TransformationChain.php',
        'phpims\\image\\transformationinterface' => '/Image/TransformationInterface.php',
        'phpims\\operation' => '/Operation.php',
        'phpims\\operation\\addimage' => '/Operation/AddImage.php',
        'phpims\\operation\\deleteimage' => '/Operation/DeleteImage.php',
        'phpims\\operation\\deleteimagemetadata' => '/Operation/DeleteImageMetadata.php',
        'phpims\\operation\\editimagemetadata' => '/Operation/EditImageMetadata.php',
        'phpims\\operation\\exception' => '/Operation/Exception.php',
        'phpims\\operation\\getimage' => '/Operation/GetImage.php',
        'phpims\\operation\\getimagemetadata' => '/Operation/GetImageMetadata.php',
        'phpims\\operation\\getimages' => '/Operation/GetImages.php',
        'phpims\\operation\\getimages\\query' => '/Operation/GetImages/Query.php',
        'phpims\\operation\\headimage' => '/Operation/HeadImage.php',
        'phpims\\operation\\plugin\\auth' => '/Operation/Plugin/Auth.php',
        'phpims\\operation\\plugin\\exception' => '/Operation/Plugin/Exception.php',
        'phpims\\operation\\plugin\\identifyimage' => '/Operation/Plugin/IdentifyImage.php',
        'phpims\\operation\\plugin\\manipulateimage' => '/Operation/Plugin/ManipulateImage.php',
        'phpims\\operation\\plugin\\prepareimage' => '/Operation/Plugin/PrepareImage.php',
        'phpims\\operation\\plugininterface' => '/Operation/PluginInterface.php',
        'phpims\\operationinterface' => '/OperationInterface.php',
        'phpims\\server\\response' => '/Server/Response.php',
        'phpims\\storage\\driver\\filesystem' => '/Storage/Driver/Filesystem.php',
        'phpims\\storage\\driverinterface' => '/Storage/DriverInterface.php',
        'phpims\\storage\\exception' => '/Storage/Exception.php'
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
}

spl_autoload_register('PHPIMS\\Autoload::load');