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

namespace PHPIMS\Storage\Driver;

use Mockery as m;

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
class FilesystemTest extends \PHPUnit_Framework_TestCase {
    /**
     * Public key that can be used in tests
     *
     * @var string
     */
    private $publicKey = '59632bc7a908b9cd47a35d03fc992aa7';

    /**
     * Image identifier that can be used in tests
     *
     * @var string
     */
    private $imageIdentifier = '96d08a5943ebf1c5635a2995c9408cdd.png';

    /**
     * @expectedException PHPIMS\Storage\Exception
     * @expectedExceptionMessage File not found
     */
    public function testDeleteFileThatDoesNotExist() {
        $driver = new Filesystem(array('dataDir' => 'foobar'));
        $driver->delete($this->publicKey, $this->imageIdentifier);
    }

    public function testDelete() {
        \vfsStream::setup('basedir');
        $driver = new Filesystem(array('dataDir' => \vfsStream::url('basedir')));

        $root = \vfsStreamWrapper::getRoot();
        $last = $root;

        $parts = array(
            $this->publicKey[0],
            $this->publicKey[1],
            $this->publicKey[2],
            $this->publicKey,
            $this->imageIdentifier[0],
            $this->imageIdentifier[1],
            $this->imageIdentifier[2],
        );

        foreach ($parts as $part) {
            $d = \vfsStream::newDirectory($part);
            $last->addChild($d);
            $last = $d;
        }

        $last->addChild(\vfsStream::newFile($this->imageIdentifier));

        $this->assertTrue($last->hasChild($this->imageIdentifier));
        $driver->delete($this->publicKey, $this->imageIdentifier);
        $this->assertFalse($last->hasChild($this->imageIdentifier));
    }

    /**
     * @expectedException PHPIMS\Storage\Exception
     * @expectedExpectionMessage Could not store image
     */
    public function testStoreToUnwritablePath() {
        $image = m::mock('PHPIMS\Image\ImageInterface');
        $dir = 'unwritableDirectory';

        // Create the virtual directory with no permissions
        \vfsStream::setup($dir, 0);

        $driver = new Filesystem(array('dataDir' => \vfsStream::url($dir)));
        $driver->store($this->publicKey, $this->imageIdentifier, $image);
    }

    public function testGetImagePath() {
        $driver = new Filesystem(array('dataDir' => '/tmp'));
        $this->assertSame(
            '/tmp/5/9/6/59632bc7a908b9cd47a35d03fc992aa7/9/6/d/96d08a5943ebf1c5635a2995c9408cdd.png',
            $driver->getImagePath($this->publicKey, $this->imageIdentifier)
        );
        $this->assertSame(
            '/tmp/5/9/6/59632bc7a908b9cd47a35d03fc992aa7/9/6/d',
            $driver->getImagePath($this->publicKey, $this->imageIdentifier, false)
        );
    }

    /**
     * @expectedException PHPIMS\Storage\Exception
     * @expectedExceptionCode 404
     */
    public function testLoadFileThatDoesNotExist() {
        $image = m::mock('PHPIMS\Image\ImageInterface');
        $driver = new Filesystem(array('dataDir' => '/some/path'));
        $driver->load($this->publicKey, $this->imageIdentifier, $image);
    }

    public function testLoad() {
        \vfsStream::setup('basedir');
        $driver = new Filesystem(array('dataDir' => \vfsStream::url('basedir')));

        $root = \vfsStreamWrapper::getRoot();
        $last = $root;

        $parts = array(
            $this->publicKey[0],
            $this->publicKey[1],
            $this->publicKey[2],
            $this->publicKey,
            $this->imageIdentifier[0],
            $this->imageIdentifier[1],
            $this->imageIdentifier[2],
        );

        foreach ($parts as $part) {
            $d = \vfsStream::newDirectory($part);
            $last->addChild($d);
            $last = $d;
        }

        $content = 'some binary content';
        $file = \vfsStream::newFile($this->imageIdentifier);
        $file->setContent($content);
        $last->addChild($file);

        $image = m::mock('PHPIMS\Image\ImageInterface');
        $image->shouldReceive('setBlob')->once()->with($content);

        $this->assertTrue($driver->load($this->publicKey, $this->imageIdentifier, $image));
    }
}
