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
    Imbo\Model\Image;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 */
abstract class TransformationTests extends \PHPUnit_Framework_TestCase {
    /**
     * Test cases must implement this method and return an instance of the transformation they are
     * testing. This transformation instance will be used for the tests in this base test
     * case as well.
     *
     * @return TransformationInterface
     */
    abstract protected function getTransformation();

    /**
     * Get the image mock used in the simple testApplyToImage
     *
     * @return Image
     */
    abstract protected function getImageMock();

    /**
     * Fetch a set of default parameters for the transformation
     *
     * @return array
     */
    abstract protected function getDefaultParams();

    /**
     * Make sure we have Imagick available
     */
    public function setUp() {
        if (!class_exists('Imagick')) {
            $this->markTestSkipped('Imagick must be available to run this test');
        }
    }

    /**
     * Simply apply the current transformation to an image instance
     *
     * The transformation instance returned from getTransformation() will be used
     */
    public function testSimpleApplyToImage() {
        $this->getTransformation()->applyToImage(
            $this->getImageMock(), $this->getDefaultParams()
        );
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     */
    public function testApplyToImageWithUnknownImageFormat() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue('some string'));
        $image->expects($this->any())->method('getWidth')->will($this->returnValue(1600));
        $image->expects($this->any())->method('getHeight')->will($this->returnValue(900));

        $this->getTransformation()->applyToImage($image, $this->getDefaultParams());
    }
}
