<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use ImagickException;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Image\OutputConverterManager;
use Imbo\Model\Image;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Convert
 */
class ConvertTest extends TransformationTests
{
    protected function getTransformation(): Convert
    {
        return new Convert();
    }

    /**
     * @covers ::transform
     */
    public function testCanConvertAnImage(): void
    {
        $image = $this->createConfiguredMock(Image::class, [
            'getExtension' => 'png',
        ]);

        $image
            ->expects($this->once())
            ->method('setMimeType')
            ->with('image/gif')
            ->willReturnSelf();

        $image
            ->expects($this->once())
            ->method('setExtension')
            ->with('gif')
            ->willReturnSelf();

        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true)
            ->willReturnSelf();

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $outputConverterManager = $this->createMock(OutputConverterManager::class);
        $outputConverterManager
            ->expects($this->any())
            ->method('getMimetypeFromExtension')
            ->with('gif')
            ->willReturn('image/gif');

        $event = $this->createConfiguredMock(EventInterface::class, [
            'getOutputConverterManager' => $outputConverterManager,
        ]);

        $this->getTransformation()
            ->setEvent($event)
            ->setImage($image)
            ->setImagick($imagick)
            ->transform(['type' => 'gif']);
    }

    /**
     * @covers ::transform
     */
    public function testWillNotConvertImageIfNotNeeded(): void
    {
        $image = $this->createConfiguredMock(Image::class, [
            'getExtension' => 'png',
        ]);
        $image->expects($this->never())->method('getBlob');

        (new Convert())
            ->setImage($image)
            ->transform(['type' => 'png']);
    }

    /**
     * @covers ::transform
     */
    public function testThrowsExceptionOnMissingType(): void
    {
        $this->expectExceptionObject(new TransformationException('Missing required parameter: type', Response::HTTP_BAD_REQUEST));
        (new Convert())->transform([]);
    }

    public function getConvertParams(): array
    {
        return [
            ['png', 'jpg', 'image/jpeg'],
            ['jpg', 'png', 'image/png'],
        ];
    }

    /**
     * @dataProvider getConvertParams
     * @covers ::transform
     */
    public function testWillConvertImages(string $existingExtension, string $newType, string $newMimeType): void
    {
        $image = $this->createConfiguredMock(Image::class, [
            'getExtension' => $existingExtension,
        ]);
        $image
            ->expects($this->once())
            ->method('setMimeType')
            ->with($newMimeType)
            ->willReturnSelf();
        $image
            ->expects($this->once())
            ->method('setExtension')
            ->with($newType)
            ->willReturnSelf();
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true)
            ->willReturnSelf();

        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('setImageFormat')
            ->with($newType);

        $converterManager = $this->createMock(OutputConverterManager::class);
        $converterManager
            ->expects($this->once())
            ->method('getMimeTypeFromExtension')
            ->with($newType)
            ->willReturn($newMimeType);

        $event = $this->createConfiguredMock(EventInterface::class, [
            'getOutputConverterManager' => $converterManager,
        ]);

        (new Convert())
            ->setEvent($event)
            ->setImage($image)
            ->setImagick($imagick)
            ->transform(['type' => $newType]);
    }

    /**
     * @covers ::transform
     */
    public function testThrowsExceptionOnImagickError(): void
    {
        $image = $this->createConfiguredMock(Image::class, [
            'getExtension' => 'png',
        ]);

        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('setImageFormat')
            ->willThrowException($e = new ImagickException('some error'));

        $this->expectExceptionObject(new TransformationException('some error', Response::HTTP_BAD_REQUEST, $e));

        (new Convert())
            ->setImage($image)
            ->setImagick($imagick)
            ->transform(['type' => 'jpg']);
    }
}
