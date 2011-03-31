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

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_Operation_Plugin_PrepareImagePluginTest extends PHPUnit_Framework_TestCase {
    /**
     * Plugin instance
     *
     * @var PHPIMS_Operation_Plugin_PrepareImagePlugin
     */
    protected $plugin = null;

    public function setUp() {
        $this->plugin = new PHPIMS_Operation_Plugin_PrepareImagePlugin();
    }

    public function tearDown() {
        $this->plugin = null;
    }

    /**
     * @expectedException PHPIMS_Operation_Plugin_Exception
     * @expectedExceptionCode 400
     */
    public function testExecWithNoImageInFilesArray() {
        $this->plugin->exec();
    }

    /**
     * @expectedException PHPIMS_Operation_Plugin_Exception
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Hash mismatch
     */
    public function testExecWithHashMismatch() {
        $_FILES['file']['tmp_name'] = __DIR__ . '/../../_files/image.png';

        $operation = $this->getMockBuilder('PHPIMS_Operation_AddImage')
                          ->disableOriginalConstructor()
                          ->getMock();

        $operation->expects($this->once())
                  ->method('getHash')
                  ->will($this->returnValue(str_repeat('a', 32) . '.png'));

        $this->plugin->setOperation($operation)->exec();
    }

    public function testSuccessfulExec() {
        $_FILES['file']['tmp_name'] = __DIR__ . '/../../_files/image.png';
        $_FILES['file']['name'] = 'image.png';
        $_FILES['file']['size'] = 41423;
        $metadata = array('foo' => 'bar', 'bar' => 'foo');
        $_POST = array('metadata' => json_encode($metadata));
        $hash = md5_file($_FILES['file']['tmp_name']) . '.png';

        $image = $this->getMock('PHPIMS_Image');
        $image->expects($this->once())
              ->method('setFilename')->with('image.png')
              ->will($this->returnValue($image));
        $image->expects($this->once())
              ->method('setFilesize')->with(41423)
              ->will($this->returnValue($image));
        $image->expects($this->once())
              ->method('setMetadata')->with($metadata)
              ->will($this->returnValue($image));
        $image->expects($this->once())
              ->method('setBlob')->with(file_get_contents($_FILES['file']['tmp_name']))
              ->will($this->returnValue($image));

        $operation = $this->getMockBuilder('PHPIMS_Operation_AddImage')
                          ->disableOriginalConstructor()
                          ->getMock();

        $operation->expects($this->once())
                  ->method('getHash')
                  ->will($this->returnValue($hash));

        $operation->expects($this->once())
                  ->method('getImage')
                  ->will($this->returnValue($image));

        $this->plugin->setOperation($operation)->exec();
    }
}