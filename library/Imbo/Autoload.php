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
 * @package Core
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo;

/**
 * Autoloader used by Imbo
 *
 * @package Core
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class Autoload {
    /**
     * Imbo classes
     *
     * @var array
     */
    static public $classes = array(
        'imbo\\autoload' => '/Autoload.php',
        'imbo\\container' => '/Container.php',
        'imbo\\database\\databaseinterface' => '/Database/DatabaseInterface.php',
        'imbo\\database\\exception' => '/Database/Exception.php',
        'imbo\\database\\mongodb' => '/Database/MongoDB.php',
        'imbo\\eventmanager\\event' => '/EventManager/Event.php',
        'imbo\\eventmanager\\eventinterface' => '/EventManager/EventInterface.php',
        'imbo\\eventmanager\\eventmanager' => '/EventManager/EventManager.php',
        'imbo\\eventmanager\\eventmanagerinterface' => '/EventManager/EventManagerInterface.php',
        'imbo\\eventmanager\\listenerinterface' => '/EventManager/ListenerInterface.php',
        'imbo\\exception' => '/Exception.php',
        'imbo\\frontcontroller' => '/FrontController.php',
        'imbo\\http\\headercontainer' => '/Http/HeaderContainer.php',
        'imbo\\http\\parametercontainer' => '/Http/ParameterContainer.php',
        'imbo\\http\\parametercontainerinterface' => '/Http/ParameterContainerInterface.php',
        'imbo\\http\\request\\request' => '/Http/Request/Request.php',
        'imbo\\http\\request\\requestinterface' => '/Http/Request/RequestInterface.php',
        'imbo\\http\\response\\formatter\\formatterinterface' => '/Http/Response/Formatter/FormatterInterface.php',
        'imbo\\http\\response\\formatter\\json' => '/Http/Response/Formatter/Json.php',
        'imbo\\http\\response\\response' => '/Http/Response/Response.php',
        'imbo\\http\\response\\responseinterface' => '/Http/Response/ResponseInterface.php',
        'imbo\\http\\response\\responsewriter' => '/Http/Response/ResponseWriter.php',
        'imbo\\http\\response\\responsewriterinterface' => '/Http/Response/ResponseWriterInterface.php',
        'imbo\\http\\servercontainer' => '/Http/ServerContainer.php',
        'imbo\\http\\servercontainerinterface' => '/Http/ServerContainerInterface.php',
        'imbo\\image\\exception' => '/Image/Exception.php',
        'imbo\\image\\image' => '/Image/Image.php',
        'imbo\\image\\imageinterface' => '/Image/ImageInterface.php',
        'imbo\\image\\imagepreparation' => '/Image/ImagePreparation.php',
        'imbo\\image\\imagepreparationinterface' => '/Image/ImagePreparationInterface.php',
        'imbo\\image\\transformation\\border' => '/Image/Transformation/Border.php',
        'imbo\\image\\transformation\\canvas' => '/Image/Transformation/Canvas.php',
        'imbo\\image\\transformation\\compress' => '/Image/Transformation/Compress.php',
        'imbo\\image\\transformation\\convert' => '/Image/Transformation/Convert.php',
        'imbo\\image\\transformation\\crop' => '/Image/Transformation/Crop.php',
        'imbo\\image\\transformation\\exception' => '/Image/Transformation/Exception.php',
        'imbo\\image\\transformation\\fliphorizontally' => '/Image/Transformation/FlipHorizontally.php',
        'imbo\\image\\transformation\\flipvertically' => '/Image/Transformation/FlipVertically.php',
        'imbo\\image\\transformation\\resize' => '/Image/Transformation/Resize.php',
        'imbo\\image\\transformation\\rotate' => '/Image/Transformation/Rotate.php',
        'imbo\\image\\transformation\\thumbnail' => '/Image/Transformation/Thumbnail.php',
        'imbo\\image\\transformation\\transformation' => '/Image/Transformation/Transformation.php',
        'imbo\\image\\transformation\\transformationinterface' => '/Image/Transformation/TransformationInterface.php',
        'imbo\\image\\transformationchain' => '/Image/TransformationChain.php',
        'imbo\\resource\\exception' => '/Resource/Exception.php',
        'imbo\\resource\\image' => '/Resource/Image.php',
        'imbo\\resource\\images' => '/Resource/Images.php',
        'imbo\\resource\\images\\query' => '/Resource/Images/Query.php',
        'imbo\\resource\\images\\queryinterface' => '/Resource/Images/QueryInterface.php',
        'imbo\\resource\\imagesinterface' => '/Resource/ImagesInterface.php',
        'imbo\\resource\\metadata' => '/Resource/Metadata.php',
        'imbo\\resource\\resource' => '/Resource/Resource.php',
        'imbo\\resource\\resourceinterface' => '/Resource/ResourceInterface.php',
        'imbo\\resource\\user' => '/Resource/User.php',
        'imbo\\storage\\exception' => '/Storage/Exception.php',
        'imbo\\storage\\filesystem' => '/Storage/Filesystem.php',
        'imbo\\storage\\storageinterface' => '/Storage/StorageInterface.php',
        'imbo\\validate\\signature' => '/Validate/Signature.php',
        'imbo\\validate\\signatureinterface' => '/Validate/SignatureInterface.php',
        'imbo\\validate\\timestamp' => '/Validate/Timestamp.php',
        'imbo\\validate\\validateinterface' => '/Validate/ValidateInterface.php',
        'imbo\\version' => '/Version.php'
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
