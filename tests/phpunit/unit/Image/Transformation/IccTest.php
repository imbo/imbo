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

use Imbo\Image\Transformation\Icc,
    Imbo\Model\Image,
    Imagick,
    ImagickException;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Icc
 * @group unit
 * @group transformations
 */
class IccTest extends \PHPUnit_Framework_TestCase {
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
     * Tear down the transformation instance
     */
    public function tearDown() {
        $this->transformation = null;
    }

    /**
     * @covers ::transform
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp #No name given for ICC profile to use and no profile#
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
     * @expectedExceptionMessageRegExp #The given ICC profile alias.*is unknown#
     * @expectedExceptionCode 400
     */
    public function testExceptionWithInvalidName() {
        $transformation = new Icc([]);
        $transformation->transform(['name' => 'foo']);
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

        $transformation->transform(['name' => 'foo']);
    }

    /**
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
}
