<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use ImagickException;
use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\Event;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Clip
 */
class ClipTest extends TestCase
{
    private Clip $transformation;
    private Image $image;
    private Imagick $imagick;

    public function setUp(): void
    {
        $this->transformation = new Clip();

        $user = 'user';
        $imageIdentifier = 'imageIdentifier';
        $blob = file_get_contents(FIXTURES_DIR . '/jpeg-with-multiple-paths.jpg');

        $this->image = $this->createConfiguredMock(Image::class, [
            'getImageIdentifier' => $imageIdentifier,
            'getUser'            => $user,
        ]);

        $database = $this->createMock(DatabaseInterface::class);
        $database
            ->method('getMetadata')
            ->with($user, $imageIdentifier)
            ->willReturn([
                'paths' => ['House', 'Panda'],
            ]);

        $event = $this->createConfiguredMock(Event::class, [
            'getDatabase' => $database,
        ]);

        $this->transformation->setEvent($event);
        $this->transformation->setImage($this->image);

        $this->imagick = new Imagick();
        $this->imagick->readImageBlob($blob);
        $this->transformation->setImagick($this->imagick);
    }

    /**
     * @covers ::transform
     */
    public function testExceptionIfMissingNamedPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/clipping path .* not found/');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);
        $this->transformation->transform(['path' => 'foo']);
    }

    /**
     * @covers ::transform
     */
    public function testNoExceptionIfMissingNamedPathButIgnoreSet(): void
    {
        $this->assertNull(
            $this->transformation->transform([
                'path' => 'foo',
                'ignoreUnknownPath' => '',
            ]),
            'Expected transform method to not return anything',
        );
    }

    /**
     * @covers ::transform
     */
    public function testTransformationHappensWithMatchingPath(): void
    {
        $this->image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true);

        $this->transformation->transform(['path' => 'Panda']);
    }

    /**
     * @covers ::transform
     */
    public function testTransformationHappensWithoutExplicitPath(): void
    {
        $this->image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true);

        $this->transformation->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testTransformationDoesntHappenWhenNoPathIsPresent(): void
    {
        $this->imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.jpg'));
        $this->image
            ->expects($this->never())
            ->method('setHasBeenTransformed');

        $this->transformation->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testWillResetAlphaChannelWhenTheImageDoesNotHaveAClippingPath(): void
    {
        $imagick = $this->createConfiguredMock(Imagick::class, [
            'getImageAlphaChannel' => Imagick::ALPHACHANNEL_COPY,
        ]);

        $imagick
            ->expects($this->exactly(2))
            ->method('setImageAlphaChannel')
            ->withConsecutive(
                [Imagick::ALPHACHANNEL_TRANSPARENT],
                [Imagick::ALPHACHANNEL_COPY], // Reset to the one fetched above
            );

        $imagick
            ->expects($this->once())
            ->method('clipImage')
            ->willThrowException(new ImagickException('some error', 410));

        $this->transformation
            ->setImagick($imagick)
            ->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testThrowsExceptionWhenImagickFailsWithAFatalError(): void
    {
        $imagick = $this->createConfiguredMock(Imagick::class, [
            'getImageAlphaChannel' => Imagick::ALPHACHANNEL_COPY,
        ]);
        $imagick
            ->expects($this->once())
            ->method('setImageAlphaChannel')
            ->with(Imagick::ALPHACHANNEL_TRANSPARENT);
        $imagick
            ->expects($this->once())
            ->method('clipImage')
            ->willThrowException(new ImagickException('Some error'));

        $this->transformation->setImagick($imagick);
        $this->expectExceptionObject(new TransformationException('Some error', Response::HTTP_BAD_REQUEST));
        $this->transformation->transform([]);
    }
}
