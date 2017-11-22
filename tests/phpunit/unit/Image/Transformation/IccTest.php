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

use Imbo\Image\Transformation\Icc;
use Imbo\Model\Image;
use Imagick;
use ImagickException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Icc
 * @group unit
 * @group transformations
 */
class IccTest extends TestCase {
    /**
     * @var Icc
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
        $blob = file_get_contents(FIXTURES_DIR . '/white.png');

        $this->image = $this->createMock('Imbo\Model\Image');

        $this->imagick = new Imagick();
        $this->imagick->readImageBlob($blob);
    }

    /**
     * @covers ::transform
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage No profile name given for which ICC profile to use and no profile is assigned to the "default" name.
     * @expectedExceptionCode 400
     *
     */
    public function testExceptionWithoutProfiles() {
        $transformation = new Icc([]);
        $transformation->transform([]);
    }

    /**
     * @covers ::transform
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage The given ICC profile name ("foo") is unknown to the server.
     * @expectedExceptionCode 400
     */
    public function testExceptionWithInvalidName() {
        $transformation = new Icc([]);
        $transformation->transform(['profile' => 'foo']);
    }

    /**
     * @covers ::transform
     */
    public function testTransformationHappensWithMatchingName() {
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
    public function testTransformationHappensWithDefaultKey() {
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
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionMessage Some error
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenImagickFailsWithAFatalError() {
        $transformation = new Icc([
            'default' => DATA_DIR . '/profiles/sRGB_v4_ICC_preference.icc',
        ]);

        $imagick = $this->createMock('Imagick');
        $imagick
            ->expects($this->once())
            ->method('profileImage')
            ->willThrowException(new ImagickException('Some error'));

        $transformation
            ->setImagick($imagick)
            ->transform([]);
    }

    /**
     * @covers ::__construct
     * @expectedException Imbo\Exception\ConfigurationException
     * @expectedExceptionMessage Imbo\Image\Transformation\Icc requires an array with name => profile file (.icc) mappings when created.
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenConstructingWithWrongType() {
        new Icc('/some/path');
    }

    /**
     * @covers ::transform
     * @expectedException Imbo\Exception\ConfigurationException
     * @expectedExceptionMessageRegExp /Could not load ICC profile referenced by "default": .*\/foo\/bar.icc/
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenInvalidPathIsUsed() {
        $transformation = new Icc([
            'default' => DATA_DIR . '/foo/bar.icc',
        ]);

        $transformation
            ->setImagick($this->imagick)
            ->setImage($this->image)
            ->transform([]);
    }
}
