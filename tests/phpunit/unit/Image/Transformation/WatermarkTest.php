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

use Imbo\Image\Transformation\Watermark;
use Imbo\Exception\StorageException;
use Imbo\Exception\TransformationException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Watermark
 * @group unit
 * @group transformations
 */
class WatermarkTest extends TestCase {
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
     * @covers ::transform
     */
    public function testTransformThrowsExceptionIfNoImageSpecified() {
        $image = $this->createMock('Imbo\Model\Image');
        $this->expectExceptionObject(new TransformationException(
            'You must specify an image identifier to use for the watermark',
            400
        ));
        $this->transformation->setImage($image)->transform([]);
    }

    /**
     * @covers ::transform
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

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getStorage')->will($this->returnValue($storage));
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $this->transformation->setImage($this->createMock('Imbo\Model\Image'));
        $this->transformation->setEvent($event);
        $this->expectExceptionObject(new TransformationException('Watermark image not found', 400));
        $this->transformation->transform(['img' => 'foobar']);
    }
}
