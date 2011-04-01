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

/** vfsStream */
require_once 'vfsStream/vfsStream.php';

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_Storage_Driver_FilesystemTest extends PHPUnit_Framework_TestCase {
    /**
     * Driver instance
     *
     * @var PHPIMS_Storage_Driver_Filesystem
     */
    protected $driver = null;

    /**
     * Set up method
     */
    public function setUp() {
        $this->driver = new PHPIMS_Storage_Driver_Filesystem();
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->driver = null;
    }

    /**
     * @expectedException PHPIMS_Storage_Exception
     * @expectedExceptionMessage File not found
     */
    public function testDeleteFileThatDoesNotExist() {
        $this->driver->setParams(array('dataDir' => 'foobar'));
        $this->driver->delete('asdasdasasd');
    }

    public function testDelete() {
        $hash = 'cfe95b64ac715d64275365ede690ee7c.png';

        vfsStream::setup('basedir');
        $this->driver->setParams(array('dataDir' => vfsStream::url('basedir')));

        $root = vfsStreamWrapper::getRoot();
        $last = $root;

        foreach (array($hash[0], $hash[1], $hash[2]) as $letter) {
            $d = vfsStream::newDirectory($letter);
            $last->addChild($d);
            $last = $d;
        }

        $last->addChild(vfsStream::newFile($hash));

        $this->assertTrue($last->hasChild($hash));
        $this->driver->delete($hash);
        $this->assertFalse($last->hasChild($hash));
    }

    /**
     * @expectedException PHPIMS_Storage_Exception
     * @expectedExpectionMessage Could not store image
     */
    public function testStoreToUnwritablePath() {
        $image = $this->getMock('PHPIMS_Image');
        $dir = 'unwritableDirectory';

        // Create the virtual directory with no permissions
        vfsStream::setup($dir, 0);

        $this->driver->setParams(array(
            'dataDir' => vfsStream::url($dir),
        ));

        $this->driver->store('some path', $image);
    }

    public function testGetImagePath() {
        $this->driver->setParams(array('dataDir' => '/tmp'));
        $this->assertSame('/tmp/a/s/d/asdasdasd.png', $this->driver->getImagePath('asdasdasd.png'));
    }

    /**
     * @expectedException PHPIMS_Storage_Exception
     * @expectedExceptionCode 404
     */
    public function testLoadFileThatDoesNotExist() {
        $image = m::mock('PHPIMS_Image');
        $this->driver->setParams(array('dataDir' => '/some/path'));
        $this->driver->load(md5(microtime()) . '.png', $image);
    }

    public function testLoad() {
        $hash = md5(microtime());

        vfsStream::setup('basedir');
        $this->driver->setParams(array('dataDir' => vfsStream::url('basedir')));

        $root = vfsStreamWrapper::getRoot();
        $last = $root;

        foreach (array($hash[0], $hash[1], $hash[2]) as $letter) {
            $d = vfsStream::newDirectory($letter);
            $last->addChild($d);
            $last = $d;
        }

        $content = 'some binary content';
        $file = vfsStream::newFile($hash);
        $file->setContent($content);
        $last->addChild($file);

        $image = $this->getMock('PHPIMS_Image');
        $image->expects($this->once())->method('setBlob')->with($content);

        $this->assertTrue($this->driver->load($hash, $image));
    }
}