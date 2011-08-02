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

/**
 * Prepare image plugin
 *
 * This plugin will kick in before the AddImage operation executes. The plugin will prepare the
 * image object so it can be added using a database and a storage driver,
 *
 * @package PHPIMS
 * @subpackage OperationPlugin
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PrepareImage implements PluginInterface {
    /**
     * Events this plugin will hook into
     *
     * @var array
     */
    static public $events = array(
        'addImagePreExec' => 101,
    );

    /**
     * @see PHPIMS\Operation\PluginInterface::exec()
     */
    public function exec(Operation $operation) {
        // Fetch image data from input
        $imageBlob = file_get_contents('php://input');

        if (empty($imageBlob)) {
            throw new Exception('No image attached', 400);
        }

        // Calculate hash
        $actualHash = md5($imageBlob);

        // Get image identifier from request
        $identifierFromRequest = $operation->getImageIdentifier();

        if ($actualHash !== substr($identifierFromRequest, 0, 32)) {
            throw new Exception('Hash mismatch', 400);
        }

        // Store file to disk and use getimagesize() to fetch width/height
        $tmpFile = tempnam(sys_get_temp_dir(), 'PHPIMS_uploaded_Image');
        file_put_contents($tmpFile, $imageBlob);
        $size = getimagesize($tmpFile);

        // Fetch the image object and store the blob
        $image = $operation->getImage();
        $image->setBlob($imageBlob)->setWidth($size[0])->setHeight($size[1]);
    }
}
