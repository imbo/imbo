<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use ImagickException;
use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\Event;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DrawPois::class)]
class DrawPoisTest extends TestCase
{
    public function testDoesNotModifyImageIfNoPoisAreFound(): void
    {
        $database = $this->createConfiguredMock(DatabaseInterface::class, [
            'getMetadata' => [],
        ]);

        $event = $this->createConfiguredMock(Event::class, [
            'getDatabase' => $database,
        ]);

        $image = $this->createConfiguredMock(Image::class, [
            'getUser' => 'user',
            'getImageIdentifier' => 'image-id',
        ]);
        $image
            ->expects($this->never())
            ->method('setHasBeenTransformed');

        $transformation = new DrawPois();
        $transformation
            ->setEvent($event)
            ->setImage($image)
            ->transform([]);
    }

    public function testDoesNotModifyImageIfNoPoiMetadataKeyIsNotAnArray(): void
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database
            ->expects($this->once())
            ->method('getMetadata')
            ->with('user', 'image identifier')
            ->willReturn(['poi' => 'wat']);

        $event = $this->createConfiguredMock(Event::class, [
            'getDatabase' => $database,
        ]);

        $image = $this->createConfiguredMock(Image::class, [
            'getUser' => 'user',
            'getImageIdentifier' => 'image identifier',
        ]);
        $image
            ->expects($this->never())
            ->method('setHasBeenTransformed');

        $transformation = new DrawPois();
        $transformation
            ->setEvent($event)
            ->setImage($image)
            ->transform([]);
    }

    public function testThrowsExceptionOnInvalidPoi(): void
    {
        $database = $this->createConfiguredMock(DatabaseInterface::class, [
            'getMetadata' => [
                'poi' => [['foo' => 'bar']],
            ],
        ]);

        $event = $this->createConfiguredMock(Event::class, [
            'getDatabase' => $database,
        ]);

        $image = $this->createConfiguredMock(Image::class, [
            'getUser' => 'user',
            'getImageIdentifier' => 'image-id',
        ]);
        $image
            ->expects($this->never())
            ->method('setHasBeenTransformed');

        $this->expectExceptionObject(new TransformationException(
            'Point of interest had neither `width` and `height` nor `cx` and `cy`',
        ));

        (new DrawPois())
            ->setEvent($event)
            ->setImage($image)
            ->transform([]);
    }

    public function testDrawsSameAmountOfTimesAsPoisArePresent(): void
    {
        $database = $this->createConfiguredMock(DatabaseInterface::class, [
            'getMetadata' => [
                'poi' => [
                    [
                        'x' => 362,
                        'y' => 80,
                        'cx' => 467,
                        'cy' => 203,
                        'width' => 210,
                        'height' => 245,
                    ],
                    [
                        'x' => 74,
                        'y' => 237,
                        'cx' => 98,
                        'cy' => 263,
                        'width' => 48,
                        'height' => 51,
                    ],
                    [
                        'cx' => 653,
                        'cy' => 185,
                    ],
                ],
            ],
        ]);

        $event = $this->createConfiguredMock(Event::class, [
            'getDatabase' => $database,
        ]);

        $image = $this->createConfiguredMock(Image::class, [
            'getWidth' => 1000,
            'getHeight' => 800,
            'getUser' => 'user',
            'getImageIdentifier' => 'image-id',
        ]);
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true);

        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->exactly(3))
            ->method('drawImage');

        (new DrawPois())
            ->setEvent($event)
            ->setImage($image)
            ->setImagick($imagick)
            ->transform([]);
    }

    public function testThrowsExceptionWhenImagickThrowsException(): void
    {
        $database = $this->createConfiguredMock(DatabaseInterface::class, [
            'getMetadata' => [
                'poi' => [[
                    'width'  => 100,
                    'height' => 100,
                    'x'      => 0,
                    'y'      => 0,
                ]],
            ],
        ]);

        $image = $this->createConfiguredMock(Image::class, [
            'getUser'            => 'user',
            'getImageIdentifier' => 'image identifier',
            'getWidth'           => 1000,
            'getHeight'          => 800,
        ]);

        $event = $this->createConfiguredMock(Event::class, [
            'getDatabase' => $database,
        ]);

        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('drawImage')
            ->willThrowException($e = new ImagickException('some error'));

        $this->expectExceptionObject(new TransformationException('some error', Response::HTTP_BAD_REQUEST, $e));

        (new DrawPois())
            ->setImage($image)
            ->setImagick($imagick)
            ->setEvent($event)
            ->transform([]);
    }
}
