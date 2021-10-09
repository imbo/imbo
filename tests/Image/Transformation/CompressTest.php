<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Compress
 */
class CompressTest extends TransformationTests
{
    private $transformation;

    protected function getTransformation(): Compress
    {
        return new Compress();
    }

    /**
     * @covers ::transform
     */
    public function testCanTransformTheImage(): void
    {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setOutputQualityCompression')
            ->with(50);

        $event = $this->createMock(EventInterface::class);

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $transformation = $this->getTransformation();
        $transformation
            ->setImagick($imagick)
            ->setImage($image)
            ->setEvent($event)
            ->transform(['level' => 50]);
    }

    public function setUp(): void
    {
        $this->transformation = new Compress();
    }

    /**
     * @covers ::transform
     */
    public function testThrowsExceptionOnMissingLevelParameter(): void
    {
        $this->expectExceptionObject(new TransformationException(
            'Missing required parameter: level',
            Response::HTTP_BAD_REQUEST,
        ));
        $this->transformation->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testThrowsExceptionOnInvalidLevel(): void
    {
        $this->expectExceptionObject(new TransformationException(
            'level must be between 0 and 100',
            Response::HTTP_BAD_REQUEST,
        ));
        $this->transformation->transform(['level' => 200]);
    }

    /**
     * @covers ::transform
     */
    public function testSetsOutputQualityCompression(): void
    {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setOutputQualityCompression')
            ->with(75)
            ->willReturnSelf();

        $this->transformation
            ->setImage($image)
            ->transform(['level' => 75]);
    }
}
