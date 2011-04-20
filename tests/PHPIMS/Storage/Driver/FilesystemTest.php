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
class FilesystemTest extends \PHPUnit_Framework_TestCase {
    /**
     * @expectedException PHPIMS\Storage\Exception
     * @expectedExceptionMessage File not found
     */
    public function testDeleteFileThatDoesNotExist() {
        $driver = new Filesystem(array('dataDir' => 'foobar'));
        $driver->delete('asdasdasasd');
    }

    public function testDelete() {
        $imageIdentifier = md5(microtime()) . '.png';

        \vfsStream::setup('basedir');
        $driver = new Filesystem(array('dataDir' => \vfsStream::url('basedir')));

        $root = \vfsStreamWrapper::getRoot();
        $last = $root;

        foreach (array($imageIdentifier[0], $imageIdentifier[1], $imageIdentifier[2]) as $letter) {
            $d = \vfsStream::newDirectory($letter);
            $last->addChild($d);
            $last = $d;
        }

        $last->addChild(\vfsStream::newFile($imageIdentifier));

        $this->assertTrue($last->hasChild($imageIdentifier));
        $driver->delete($imageIdentifier);
        $this->assertFalse($last->hasChild($imageIdentifier));
    }

    /**
     * @expectedException PHPIMS\Storage\Exception
     * @expectedExpectionMessage Could not store image
     */
    public function testStoreToUnwritablePath() {
        $image = m::mock('PHPIMS\\Image');
        $dir = 'unwritableDirectory';

        // Create the virtual directory with no permissions
        \vfsStream::setup($dir, 0);

        $driver = new Filesystem(array('dataDir' => \vfsStream::url($dir)));
        $driver->store('some path', $image);
    }

    public function testGetImagePath() {
        $driver = new Filesystem(array('dataDir' => '/tmp'));
        $this->assertSame('/tmp/a/s/d/asdasdasd.png', $driver->getImagePath('asdasdasd.png'));
        $this->assertSame('/tmp/a/s/d', $driver->getImagePath('asdasdasd.png', false));
    }

    /**
     * @expectedException PHPIMS\Storage\Exception
     * @expectedExceptionCode 404
     */
    public function testLoadFileThatDoesNotExist() {
        $image = m::mock('PHPIMS\\Image');
        $driver = new Filesystem(array('dataDir' => '/some/path'));
        $driver->load(md5(microtime()) . '.png', $image);
    }

    public function testLoad() {
        $imageIdentifier = md5(microtime()) . '.png';

        \vfsStream::setup('basedir');
        $driver = new Filesystem(array('dataDir' => \vfsStream::url('basedir')));

        $root = \vfsStreamWrapper::getRoot();
        $last = $root;

        foreach (array($imageIdentifier[0], $imageIdentifier[1], $imageIdentifier[2]) as $letter) {
            $d = \vfsStream::newDirectory($letter);
            $last->addChild($d);
            $last = $d;
        }

        $content = 'some binary content';
        $file = \vfsStream::newFile($imageIdentifier);
        $file->setContent($content);
        $last->addChild($file);

        $image = m::mock('PHPIMS\\Image');
        $image->shouldReceive('setBlob')->once()->with($content);

        $this->assertTrue($driver->load($imageIdentifier, $image));
    }
}