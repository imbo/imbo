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

use Imbo\Image\Transformation\Clip;
use Imbo\Model\Image;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\TransformationException;
use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\Event;
use Imagick;
use ImagickException;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Image\Transformation\Clip
 * @coversDefaultClass Imbo\Image\Transformation\Clip
 * @group unit
 * @group transformations
 */
class ClipTest extends TestCase {
    /**
     * @var Clip
     */
    private $transformation;

    /**
     * @var Image
     */
    private $image;

    /**
     * Imagick instance for testing
     *
     * @var Imagick
     */
    private $imagick;

    /**
     * Set up the transformation instance
     */
    public function setUp() {
        $this->transformation = new Clip();

        $user = 'user';
        $imageIdentifier = 'imageIdentifier';
        $blob = file_get_contents(FIXTURES_DIR . '/jpeg-with-multiple-paths.jpg');

        $this->image = $this->createConfiguredMock(Image::class, [
            'getImageIdentifier' => $imageIdentifier,
            'getUser' => $user,
        ]);

        $database = $this->createMock(DatabaseInterface::class);
        $database->method('getMetadata')
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
    public function testExceptionIfMissingNamedPath() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/clipping path .* not found/');
        $this->expectExceptionCode(400);
        $this->transformation->transform(['path' => 'foo']);
    }

    /**
     * @covers ::transform
     */
    public function testNoExceptionIfMissingNamedPathButIgnoreSet() {
        $this->assertNull(
            $this->transformation->transform([
                'path' => 'foo',
                'ignoreUnknownPath' => '',
            ]),
            'Expected transform method to not return anything'
        );
    }

    /**
     * @covers ::transform
     */
    public function testTransformationHappensWithMatchingPath() {
        $this->image->expects($this->once())
                    ->method('hasBeenTransformed')
                    ->with(true);

        $this->transformation->transform(['path' => 'Panda']);
    }

    /**
     * @covers ::transform
     */
    public function testTransformationHappensWithoutExplicitPath() {
        $this->image->expects($this->once())
                    ->method('hasBeenTransformed')
                    ->with(true);

        $this->transformation->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testTransformationDoesntHappenWhenNoPathIsPresent() {
        $this->imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.jpg'));
        $this->image->expects($this->never())
                    ->method('hasBeenTransformed');

        $this->transformation->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testWillResetAlphaChannelWhenTheImageDoesNotHaveAClippingPath() {
        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->once())
                ->method('getImageAlphaChannel')
                ->willReturn(Imagick::ALPHACHANNEL_COPY);

        $imagick->expects($this->exactly(2))
                ->method('setImageAlphaChannel')
                ->withConsecutive(
                    [Imagick::ALPHACHANNEL_TRANSPARENT],
                    [Imagick::ALPHACHANNEL_COPY] // Reset to the one fetched above
                );

        $imagick->expects($this->once())
                ->method('clipImage')
                ->willThrowException(new ImagickException('some error', 410));

        $this->transformation->setImagick($imagick)
                             ->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testThrowsExceptionWhenImagickFailsWithAFatalError() {
        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->once())
                ->method('getImageAlphaChannel')
                ->willReturn(Imagick::ALPHACHANNEL_COPY);

        $imagick->expects($this->once())
                ->method('setImageAlphaChannel')
                ->with(Imagick::ALPHACHANNEL_TRANSPARENT);

        $imagick->expects($this->once())
                ->method('clipImage')
                ->willThrowException(new ImagickException('Some error'));

        $this->transformation->setImagick($imagick);
        $this->expectExceptionObject(new TransformationException('Some error', 400));
        $this->transformation->transform([]);
    }
}
