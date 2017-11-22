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
use Imagick;
use ImagickException;
use PHPUnit\Framework\TestCase;

/**
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

        $this->image = $this->createMock('Imbo\Model\Image');
        $this->image->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $this->image->expects($this->any())->method('getUser')->will($this->returnValue($user));

        $database = $this->createMock('Imbo\Database\DatabaseInterface');
        $database->expects($this->any())->method('getMetadata')->with($user, $imageIdentifier)->will($this->returnValue([
            'paths' => ['House', 'Panda'],
        ]));

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getDatabase')->will($this->returnValue($database));

        $this->transformation->setEvent($event);
        $this->transformation->setImage($this->image);

        $this->imagick = new Imagick();
        $this->imagick->readImageBlob($blob);
        $this->transformation->setImagick($this->imagick);
    }

    /**
     * @covers ::transform
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp #clipping path .* not found#
     * @expectedExceptionCode 400
     *
     */
    public function testExceptionIfMissingNamedPath() {
        $this->transformation->transform(['path' => 'foo']);
    }

    /**
     * @covers ::transform
     */
    public function testNoExceptionIfMissingNamedPathButIgnoreSet() {
        $this->transformation->transform(['path' => 'foo', 'ignoreUnknownPath' => '']);
    }

    /**
     * @covers ::transform
     */
    public function testTransformationHappensWithMatchingPath() {
        $this->image->expects($this->atLeastOnce())->method('hasBeenTransformed')->with(true);
        $this->transformation->transform(['path' => 'Panda']);
    }

    /**
     * @covers ::transform
     */
    public function testTransformationHappensWithoutExplicitPath() {
        $this->image->expects($this->atLeastOnce())->method('hasBeenTransformed')->with(true);
        $this->transformation->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testTransformationDoesntHappenWhenNoPathIsPresent() {
        $this->imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.jpg'));
        $this->image->expects($this->never())->method('hasBeenTransformed');

        $this->transformation->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testWillResetAlphaChannelWhenTheImageDoesNotHaveAClippingPath() {
        $imagick = $this->createMock('Imagick');
        $imagick
            ->expects($this->once())
            ->method('getImageAlphaChannel')
            ->willReturn(Imagick::ALPHACHANNEL_COPY);

        $imagick
            ->expects($this->exactly(2))
            ->method('setImageAlphaChannel')
            ->withConsecutive(
                [Imagick::ALPHACHANNEL_TRANSPARENT],
                [Imagick::ALPHACHANNEL_COPY] // Reset to the one fetched above
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
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionMessage Some error
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenImagickFailsWithAFatalError() {
        $imagick = $this->createMock('Imagick');
        $imagick
            ->expects($this->once())
            ->method('getImageAlphaChannel')
            ->willReturn(Imagick::ALPHACHANNEL_COPY);

        $imagick
            ->expects($this->once())
            ->method('setImageAlphaChannel')
            ->with(Imagick::ALPHACHANNEL_TRANSPARENT);

        $imagick
            ->expects($this->once())
            ->method('clipImage')
            ->willThrowException(new ImagickException('Some error'));

        $this->transformation
            ->setImagick($imagick)
            ->transform([]);
    }
}
