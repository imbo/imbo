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

use Imbo\Image\Transformation\Compress;

/**
 * @covers Imbo\Image\Transformation\Compress
 * @group unit
 * @group transformations
 */
class CompressTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Compress
     */
    private $transformation;

    /**
     * Set up the transformation
     */
    public function setUp() {
        $this->transformation = new Compress();
    }

    /**
     * Tear down the transformation
     */
    public function tearDown() {
        $this->transformation = null;
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionMessage Missing required parameter: level
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionOnMissingLevelParameter() {
        $this->transformation->transform([]);
    }

    public function testDoesNotApplyCompressionToGifImages() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getMimeType')->will($this->returnValue('image/gif'));

        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->never())->method('setImageCompressionQuality');

        $this->transformation->setImagick($imagick)->setImage($image)->transform(['level' => 40]);
        $this->transformation->compress($this->createMock('Imbo\EventManager\EventInterface'));
    }

    public function testDoesNotApplyCompressionWhenLevelIsNotSet() {
        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->never())->method('setImageCompressionQuality');

        $this->transformation->setImagick($imagick)->compress(
            $this->createMock('Imbo\EventManager\Event')
        );
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionMessage level must be between 0 and 100
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionOnInvalidLevel() {
        $this->transformation->transform(['level' => 200]);
    }
}
