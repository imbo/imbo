<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\IntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\TransformationInterface,
    Imbo\Image\Image;

/**
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
abstract class TransformationTests extends \PHPUnit_Framework_TestCase {
    /**
     * Test cases must implement this method and return a configured instande of the transformation
     * they are testing. This transformation instance will be used for the tests in this base test
     * case
     *
     * @return TransformationInterface
     */
    abstract protected function getTransformation();

    /**
     * @return string
     */
    abstract protected function getExpectedName();

    /**
     * Get the image mock used in the simple testApplyToImage
     *
     * @return Image
     */
    abstract protected function getImageMock();

    /**
     * Make sure we have Imagick available
     */
    public function setUp() {
        if (!class_exists('Imagick')) {
            $this->markTestSkipped('Imagick must be available to run this test');
        }
    }

    /**
     * Make sure the transformation returns the expected name
     *
     * @covers Imbo\Image\Transformation\Transformation::getName
     */
    public function testGetName() {
        $this->assertSame($this->getTransformation()->getName(), $this->getExpectedName());
    }

    /**
     * Simply apply the current transformation to an image instance
     *
     * The transformation instance returned from getTransformation() will be used
     */
    public function testSimpleApplyToImage() {
        $image = $this->getImageMock();

        $this->getTransformation()->applyToImage($image);
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     */
    public function testApplyToImageWithUnknownImageFormat() {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue('some string'));
        $image->expects($this->any())->method('getWidth')->will($this->returnValue(1600));
        $image->expects($this->any())->method('getHeight')->will($this->returnValue(900));

        $this->getTransformation()->applyToImage($image);
    }
}
