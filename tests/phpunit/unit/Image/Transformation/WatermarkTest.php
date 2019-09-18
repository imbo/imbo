<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imbo\Model\Image;
use Imbo\Storage\StorageInterface;
use Imbo\EventManager\Event;
use Imbo\Http\Request\Request;
use Imbo\Exception\StorageException;
use Imbo\Exception\TransformationException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Watermark
 */
class WatermarkTest extends TestCase {
    private $transformation;

    public function setUp() : void {
        $this->transformation = new Watermark();
    }

    /**
     * @covers ::transform
     */
    public function testTransformThrowsExceptionIfNoImageSpecified() : void {
        $image = $this->createMock(Image::class);
        $this->expectExceptionObject(new TransformationException(
            'You must specify an image identifier to use for the watermark',
            400
        ));
        $this->transformation->setImage($image)->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testThrowsExceptionIfSpecifiedImageIsNotFound() : void {
        $e = new StorageException('File not found', 404);

        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('getImage')
            ->with('someuser', 'foobar')
            ->willThrowException($e);

        $request = $this->createConfiguredMock(Request::class, [
            'getUser' => 'someuser'
        ]);

        $event = $this->createConfiguredMock(Event::class, [
            'getStorage' => $storage,
            'getRequest' => $request,
        ]);

        $this->transformation
            ->setImage($this->createMock(Image::class))
            ->setEvent($event);

        $this->expectExceptionObject(new TransformationException(
            'Watermark image not found',
            400
        ));

        $this->transformation->transform(['img' => 'foobar']);
    }
}
