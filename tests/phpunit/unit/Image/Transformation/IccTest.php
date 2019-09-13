<?php
namespace ImboUnitTest\Image\Transformation;

use Imbo\Image\Transformation\Icc;
use Imbo\Model\Image;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\TransformationException;
use Imbo\Exception\ConfigurationException;
use Imagick;
use ImagickException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Icc
 */
class IccTest extends TestCase {
    private $image;
    private $imagick;

    public function setUp() : void {
        $blob = file_get_contents(FIXTURES_DIR . '/white.png');

        $this->image = $this->createMock('Imbo\Model\Image');

        $this->imagick = new Imagick();
        $this->imagick->readImageBlob($blob);
    }

    /**
     * @covers ::transform
     */
    public function testExceptionWithoutProfiles() : void {
        $transformation = new Icc([]);
        $this->expectExceptionObject(new InvalidArgumentException(
            'No profile name given for which ICC profile to use and no profile is assigned to the "default" name.',
            400
        ));
        $transformation->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testExceptionWithInvalidName() : void {
        $transformation = new Icc([]);
        $this->expectExceptionObject(new InvalidArgumentException(
            'The given ICC profile name ("foo") is unknown to the server.',
            400
        ));
        $transformation->transform(['profile' => 'foo']);
    }

    /**
     * @covers ::transform
     */
    public function testTransformationHappensWithMatchingName() : void {
        $transformation = new Icc([
            'foo' => DATA_DIR . '/profiles/sRGB_v4_ICC_preference.icc',
        ]);
        $transformation->setImagick($this->imagick);
        $transformation->setImage($this->image);
        $this->image->expects($this->atLeastOnce())->method('hasBeenTransformed')->with(true);

        $transformation->transform(['profile' => 'foo']);
    }

    /**
     * @covers ::__construct
     * @covers ::transform
     */
    public function testTransformationHappensWithDefaultKey() : void {
        $transformation = new Icc([
            'default' => DATA_DIR . '/profiles/sRGB_v4_ICC_preference.icc',
        ]);
        $transformation->setImagick($this->imagick);
        $transformation->setImage($this->image);
        $this->image->expects($this->atLeastOnce())->method('hasBeenTransformed')->with(true);

        $transformation->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testThrowsExceptionWhenImagickFailsWithAFatalError() : void {
        $transformation = new Icc([
            'default' => DATA_DIR . '/profiles/sRGB_v4_ICC_preference.icc',
        ]);

        $imagick = $this->createMock('Imagick');
        $imagick
            ->expects($this->once())
            ->method('profileImage')
            ->willThrowException(new ImagickException('Some error'));

        $transformation->setImagick($imagick);
        $this->expectExceptionObject(new TransformationException('Some error', 400));
        $transformation->transform([]);
    }

    /**
     * @covers ::__construct
     */
    public function testThrowsExceptionWhenConstructingWithWrongType() : void {
        $this->expectExceptionObject(new ConfigurationException(
            'Imbo\Image\Transformation\Icc requires an array with name => profile file (.icc) mappings when created.',
            500
        ));
        new Icc('/some/path');
    }

    /**
     * @covers ::transform
     */
    public function testThrowsExceptionWhenInvalidPathIsUsed() : void {
        $transformation = new Icc([
            'default' => DATA_DIR . '/foo/bar.icc',
        ]);

        $transformation->setImagick($this->imagick);
        $transformation->setImage($this->image);
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageRegExp(
            '/Could not load ICC profile referenced by "default": .*\/foo\/bar.icc/'
        );
        $this->expectExceptionCode(500);
        $transformation->transform([]);
    }
}
