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
        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getArgument')->with('params')->will($this->returnValue([]));
        $this->transformation->transform($event);
    }

    public function testDoesNotApplyCompressionToGifImages() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getMimeType')->will($this->returnValue('image/gif'));

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('params')->will($this->returnValue(['level' => 40]));
        $event->expects($this->at(1))->method('getArgument')->with('image')->will($this->returnValue($image));

        $imagick = $this->getMock('Imagick');
        $imagick->expects($this->never())->method('setImageCompressionQuality');

        $this->transformation->setImagick($imagick)->transform($event);
        $this->transformation->compress($event);
    }

    public function testDoesNotApplyCompressionWhenLevelIsNotSet() {
        $imagick = $this->getMock('Imagick');
        $imagick->expects($this->never())->method('setImageCompressionQuality');

        $this->transformation->setImagick($imagick)
                             ->compress($this->getMock('Imbo\EventManager\Event'));
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionMessage level must be between 0 and 100
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionOnInvalidLevel() {
        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getArgument')->with('params')->will($this->returnValue(['level' => 200]));
        $this->transformation->transform($event);
    }
}
