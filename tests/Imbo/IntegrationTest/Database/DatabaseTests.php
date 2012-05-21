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
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\IntegrationTest\Database;

use Imbo\Image\Image,
    Imbo\Resource\Images\Query;

/**
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
abstract class DatabaseTests extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\Database\DatabaseInterface
     */
    private $driver;

    /**
     * @var string
     */
    private $publicKey = 'key';

    /**
     * @var string
     */
    private $imageIdentifier = '9cb263819af35064af0b6665a1b0fddd';

    /**
     * @var Imbo\Image\ImageInterface
     */
    private $image;

    /**
     * @var string
     */
    private $imageData;

    /**
     * Get the driver we want to test
     *
     * @return Imbo\Database\DatabaseInterface
     */
    abstract protected function getDriver();

    /**
     * Set up
     */
    public function setUp() {
        $this->imageData = file_get_contents(FIXTURES_DIR . '/image.png');

        $this->image = $this->getMock('Imbo\Image\ImageInterface');
        $this->image->expects($this->any())->method('getFilesize')->will($this->returnValue(strlen($this->imageData)));
        $this->image->expects($this->any())->method('getExtension')->will($this->returnValue('png'));
        $this->image->expects($this->any())->method('getMimeType')->will($this->returnValue('image/png'));
        $this->image->expects($this->any())->method('getWidth')->will($this->returnValue(665));
        $this->image->expects($this->any())->method('getHeight')->will($this->returnValue(463));
        $this->image->expects($this->any())->method('getBlob')->will($this->returnValue($this->imageData));

        $this->driver = $this->getDriver();
    }

    /**
     * Tear down
     */
    public function tearDown() {
        $this->imageData = null;
        $this->image = null;
        $this->driver = null;
    }

    public function testInsertAndGetImage() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));

        $image = $this->getMock('Imbo\Image\ImageInterface');
        $image->expects($this->once())->method('setWidth')->with(665)->will($this->returnSelf());
        $image->expects($this->once())->method('setHeight')->with(463)->will($this->returnSelf());
        $image->expects($this->once())->method('setMimeType')->with('image/png')->will($this->returnSelf());
        $image->expects($this->once())->method('setExtension')->with('png')->will($this->returnSelf());

        $this->assertTrue($this->driver->load($this->publicKey, $this->imageIdentifier, $image));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Image already exists
     */
    public function testStoreSameImageTwice() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testStoreDeleteAndGetImage() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $this->assertTrue($this->driver->deleteImage($this->publicKey, $this->imageIdentifier));
        $this->driver->load($this->publicKey, $this->imageIdentifier, $this->getMock('Imbo\Image\ImageInterface'));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testDeleteImageThatDoesNotExist() {
        $this->driver->deleteImage($this->publicKey, $this->imageIdentifier);
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testLoadImageThatDoesNotExist() {
        $this->driver->load($this->publicKey, $this->imageIdentifier, $this->getMock('Imbo\Image\ImageInterface'));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testGetLastModifiedOfImageThatDoesNotExist() {
        $this->driver->getLastModified($this->publicKey, 'foobar');
    }

    public function testGetLastModified() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $this->assertInstanceOf('DateTime', $this->driver->getLastModified($this->publicKey, $this->imageIdentifier));
    }

    public function testGetLastModifiedWhenUserHasNoImages() {
        $this->assertInstanceOf('DateTime', $this->driver->getLastModified($this->publicKey));
    }

    public function testGetNumImages() {
        $this->assertSame(0, $this->driver->getNumImages($this->publicKey));
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $this->assertSame(1, $this->driver->getNumImages($this->publicKey));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testGetMetadataWhenImageDoesNotExist() {
        $this->driver->getMetadata($this->publicKey, $this->imageIdentifier);
    }

    public function testGetMetadataWhenImageHasNone() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $this->assertSame(array(), $this->driver->getMetadata($this->publicKey, $this->imageIdentifier));
    }

    public function testUpdateAndGetMetadata() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $this->assertTrue($this->driver->updateMetadata($this->publicKey, $this->imageIdentifier, array('foo' => 'bar')));
        $this->assertSame(array('foo' => 'bar'), $this->driver->getMetadata($this->publicKey, $this->imageIdentifier));
        $this->assertTrue($this->driver->updateMetadata($this->publicKey, $this->imageIdentifier, array('foo' => 'foo', 'bar' => 'foo')));
        $this->assertSame(array('foo' => 'foo', 'bar' => 'foo'), $this->driver->getMetadata($this->publicKey, $this->imageIdentifier));
    }

    public function testUpdateDeleteAndGetMetadata() {
        $this->assertTrue($this->driver->insertImage($this->publicKey, $this->imageIdentifier, $this->image));
        $this->assertTrue($this->driver->updateMetadata($this->publicKey, $this->imageIdentifier, array('foo' => 'bar')));
        $this->assertSame(array('foo' => 'bar'), $this->driver->getMetadata($this->publicKey, $this->imageIdentifier));
        $this->assertTrue($this->driver->deleteMetadata($this->publicKey, $this->imageIdentifier));
        $this->assertSame(array(), $this->driver->getMetadata($this->publicKey, $this->imageIdentifier));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testDeleteMetataFromImageThatDoesNotExist() {
        $this->driver->deleteMetadata($this->publicKey, $this->imageIdentifier);
    }

    private function insertImages() {
        $images = array();

        $images[0] = new Image();
        $images[0]->setMimeType('image/png')->setExtension('png')->setWidth(665)->setHeight(463)->setBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $images[1] = new Image();
        $images[1]->setMimeType('image/png')->setExtension('png')->setWidth(599)->setHeight(417)->setBlob(file_get_contents(FIXTURES_DIR . '/image1.png'));

        $images[2] = new Image();
        $images[2]->setMimeType('image/png')->setExtension('png')->setWidth(539)->setHeight(375)->setBlob(file_get_contents(FIXTURES_DIR . '/image2.png'));

        $images[3] = new Image();
        $images[3]->setMimeType('image/png')->setExtension('png')->setWidth(485)->setHeight(338)->setBlob(file_get_contents(FIXTURES_DIR . '/image3.png'));

        $images[4] = new Image();
        $images[4]->setMimeType('image/png')->setExtension('png')->setWidth(437)->setHeight(304)->setBlob(file_get_contents(FIXTURES_DIR . '/image4.png'));

        $start = time();
        sleep(1);

        foreach ($images as $index => $image) {
            $imageIdentifier = md5($image->getBlob());

            $this->driver->insertImage($this->publicKey, $imageIdentifier, $image);
            $metadata = array('key' . $index => 'value' . $index);
            $this->driver->updateMetadata($this->publicKey, $imageIdentifier, $metadata);

            sleep(1);
        }

        $end = time();

        return array($start, $end);
    }

    public function testGetImages() {
        list($start, $end) = $this->insertImages();

        // Empty query
        $query = new Query();
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(5, $images);

        // Query with end timestamp
        $query = new Query();
        $query->to($end);

        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(5, $images);

        $query = new Query();
        $query->to($start);

        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(0, $images);

        // Query with start timestamp
        $query = new Query();
        $query->from($start);

        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(5, $images);

        $query = new Query();
        $query->from($end);

        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(0, $images);

        // Make sure the result has the correct keys
        $query = new Query();
        $images = $this->driver->getImages($this->publicKey, $query);

        foreach (array('added', 'extension', 'height', 'width', 'imageIdentifier', 'mime', 'publicKey', 'size') as $key) {
            $this->assertArrayHasKey($key, $images[0]);
        }

        $query = new Query();
        $query->returnMetadata(true);
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertArrayHasKey('metadata', $images[0]);
        $this->assertArrayHasKey('metadata', $images[1]);
        $this->assertArrayHasKey('metadata', $images[2]);
        $this->assertArrayHasKey('metadata', $images[3]);
        $this->assertArrayHasKey('metadata', $images[4]);
        $this->assertSame(array('key4' => 'value4'), $images[0]['metadata']);
        $this->assertSame(array('key3' => 'value3'), $images[1]['metadata']);
        $this->assertSame(array('key2' => 'value2'), $images[2]['metadata']);
        $this->assertSame(array('key1' => 'value1'), $images[3]['metadata']);
        $this->assertSame(array('key0' => 'value0'), $images[4]['metadata']);

        // Test page and limit
        $query = new Query();
        $query->limit(2);
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(2, $images);

        $query = new Query();
        $query->limit(2)->page(1);
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(2, $images);
        $this->assertSame('a501051db16e3cbf88ea50bfb0138a47', $images[0]['imageIdentifier']);
        $this->assertSame('1d5b88aec8a3e1c4c57071307b2dae3a', $images[1]['imageIdentifier']);

        $query = new Query();
        $query->limit(2)->page(2);
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(2, $images);
        $this->assertSame('b914b28f4d5faa516e2049b9a6a2577c', $images[0]['imageIdentifier']);
        $this->assertSame('fc7d2d06993047a0b5056e8fac4462a2', $images[1]['imageIdentifier']);

        $query = new Query();
        $query->limit(2)->page(3);
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertCount(1, $images);
        $this->assertSame('929db9c5fc3099f7576f5655207eba47', $images[0]['imageIdentifier']);

        $query = new Query();
        $query->limit(2)->page(4);
        $images = $this->driver->getImages($this->publicKey, $query);
        $this->assertSame(array(), $images);
    }

    public function testGetImageMimeType() {
        $images = array();

        $images[0] = new Image();
        $images[0]->setMimeType('image/png')->setExtension('png')->setWidth(665)->setHeight(463)->setBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $images[1] = new Image();
        $images[1]->setMimeType('image/jpeg')->setExtension('jpg')->setWidth(665)->setHeight(463)->setBlob(file_get_contents(FIXTURES_DIR . '/image.jpg'));

        foreach ($images as $image) {
            $imageIdentifier = md5($image->getBlob());

            $this->driver->insertImage($this->publicKey, $imageIdentifier, $image);
        }

        $this->assertSame('image/png', $this->driver->getImageMimeType($this->publicKey, md5($images[0]->getBlob())));
        $this->assertSame('image/jpeg', $this->driver->getImageMimeType($this->publicKey, md5($images[1]->getBlob())));
    }

    /**
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Image not found
     */
    public function testGetMimeTypeWhenImageDoesNotExist() {
        $this->driver->getImageMimeType($this->publicKey, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
    }
}
