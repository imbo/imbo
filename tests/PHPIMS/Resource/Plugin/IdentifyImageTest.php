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
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Resource\Plugin;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class IdentifyImageTest extends \PHPUnit_Framework_TestCase {
    private $image;
    private $plugin;
    private $request;
    private $response;
    private $database;
    private $storage;

    public function setUp() {
        $this->image    = $this->getMock('PHPIMS\Image\ImageInterface');
        $this->plugin   = new IdentifyImage($this->image);
        $this->request  = $this->getMock('PHPIMS\Http\Request\RequestInterface');
        $this->response = $this->getMock('PHPIMS\Http\Response\ResponseInterface');
        $this->database = $this->getMock('PHPIMS\Database\DatabaseInterface');
        $this->storage  = $this->getMock('PHPIMS\Storage\StorageInterface');
    }

    public function tearDown() {
        $this->plugin = null;
        $this->image = null;
        $this->request = null;
        $this->response = null;
        $this->database = null;
        $this->storage = null;
    }

    /**
     * @expectedException PHPIMS\Resource\Plugin\Exception
     * @expectedExceptionMessage Unsupported image type
     * @expectedExceptionCode 415
     */
    public function testExecWithUnsupportedImageType() {
        $this->image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(__FILE__)));
        $this->plugin->exec($this->request, $this->response, $this->database, $this->storage);
    }

    public function testSuccessfulExec() {
        // Path to a PNG image
        $imagePath = __DIR__ . '/../../_files/image.png';

        // Image identifier with wrong extension
        $imageIdentifier = md5_file($imagePath) . '.jpg';
        $correctImageIdentifier = md5_file($imagePath) . '.png';

        $this->image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents($imagePath)));
        $this->image->expects($this->once())->method('setMimeType')->with('image/png')->will($this->returnValue($this->image));
        $this->image->expects($this->once())->method('setExtension')->with('png')->will($this->returnValue($this->image));

        // Intentionally set a wrong extension to make sure the plugin will fix it for us
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $this->request->expects($this->once())->method('setImageIdentifier')->with($correctImageIdentifier);

        $headers = $this->getMock('PHPIMS\Http\HeaderContainer');
        $headers->expects($this->once())->method('set')->with('Content-Type', 'image/png');
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($headers));

        $this->plugin->exec($this->request, $this->response, $this->database, $this->storage);
    }
}
