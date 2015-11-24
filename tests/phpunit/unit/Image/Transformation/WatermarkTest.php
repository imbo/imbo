<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Image\Transformation;

use Imbo\Image\Transformation\Watermark,
    Imbo\Exception\StorageException;

/**
 * @covers Imbo\Image\Transformation\Watermark
 * @group unit
 * @group transformations
 */
class WatermarkTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Watermark
     */
    private $transformation;

    /**
     * Set up the transformation
     */
    public function setUp() {
        $this->transformation = new Watermark();
    }

    /**
     * Tear down the transformation
     */
    public function tearDown() {
        $this->transformation = null;
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage You must specify an image identifier to use for the watermark
     */
    public function testTransformThrowsExceptionIfNoImageSpecified() {
        $image = $this->createMock('Imbo\Model\Image');

        $this->transformation->setImage($image)->transform([]);
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Watermark image not found
     */
    public function testThrowsExceptionIfSpecifiedImageIsNotFound() {
        $e = new StorageException('File not found', 404);

        $storage = $this->createMock('Imbo\Storage\StorageInterface');
        $storage->expects($this->once())
                ->method('getImage')
                ->with('someuser', 'foobar')
                ->will($this->throwException($e));

        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getUser')->will($this->returnValue('someuser'));

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getStorage')->will($this->returnValue($storage));
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $this->transformation
            ->setImage($this->getMock('Imbo\Model\Image'))
            ->setEvent($event)
            ->transform(['img' => 'foobar']);
    }

}
