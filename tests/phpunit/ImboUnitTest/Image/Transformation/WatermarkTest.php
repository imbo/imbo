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
        $image = $this->getMock('Imbo\Model\Image');

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue([]));

        $this->transformation->transform($event);
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Watermark image not found
     */
    public function testThrowsExceptionIfSpecifiedImageIsNotFound() {
        $image = $this->getMock('Imbo\Model\Image');

        $e = new StorageException('File not found', 404);

        $storage = $this->getMock('Imbo\Storage\StorageInterface');
        $storage->expects($this->once())
                ->method('getImage')
                ->with('someuser', 'foobar')
                ->will($this->throwException($e));

        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getUser')->will($this->returnValue('someuser'));

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue([
            'img' => 'foobar',
        ]));
        $event->expects($this->at(2))->method('getStorage')->will($this->returnValue($storage));
        $event->expects($this->at(3))->method('getRequest')->will($this->returnValue($request));

        $this->transformation->transform($event);
    }

}
