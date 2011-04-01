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

use \Mockery as m;

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
        $operation = m::mock('PHPIMS_Operation_Abstract');
        $this->plugin->exec($operation);
    }

    /**
     * @expectedException PHPIMS_Operation_Plugin_Exception
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Hash mismatch
     */
    public function testExecWithHashMismatch() {
        $_FILES['file']['tmp_name'] = __DIR__ . '/../../_files/image.png';

        $operation = m::mock('PHPIMS_Operation_AddImage');
        $operation->shouldReceive('getHash')->once()->andReturn(str_repeat('a', 32) . '.png');

        $this->plugin->exec($operation);
    }

    public function testSuccessfulExec() {
        $_FILES['file']['tmp_name'] = __DIR__ . '/../../_files/image.png';
        $_FILES['file']['name'] = 'image.png';
        $_FILES['file']['size'] = 41423;
        $metadata = array('foo' => 'bar', 'bar' => 'foo');
        $_POST = array('metadata' => json_encode($metadata));
        $hash = md5_file($_FILES['file']['tmp_name']) . '.png';

        $image = m::mock('PHPIMS_Image');
        $image->shouldReceive('setFilename')->once()->with('image.png')->andReturn($image);
        $image->shouldReceive('setFilesize')->once()->with(41423)->andReturn($image);
        $image->shouldReceive('setMetadata')->once()->with($metadata)->andReturn($image);
        $image->shouldReceive('setBlob')->once()->with(file_get_contents($_FILES['file']['tmp_name']))->andReturn($image);

        $operation = m::mock('PHPIMS_Operation_AddImage');
        $operation->shouldReceive('getHash')->once()->andReturn($hash);
        $operation->shouldReceive('getImage')->once()->andReturn($image);

        $this->plugin->exec($operation);
    }
}