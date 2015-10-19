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

use Imbo\Http\Response\Response;
use Imbo\Image\Transformation\DrawPois;
use Imbo\Model\Image;

/**
 * @covers Imbo\Image\Transformation\DrawPois
 * @group unit
 * @group transformations
 */
class DrawPoisTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\Image\Transformation\DrawPois::transform
     */
    public function testDoesNotModifyImageIfNoPoisAreFound() {
        $image = $this->getMock('Imbo\Model\Image');
        $database = $this->getMock('Imbo\Database\DatabaseInterface');
        $database->expects($this->once())->method('getMetadata')->will($this->returnValue([]));

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->any())->method('getDatabase')->will($this->returnValue($database));

        $image->expects($this->never())->method('hasBeenTransformed');

        $transformation = new DrawPois();
        $transformation->transform($event);
    }

    /**
     * @covers Imbo\Image\Transformation\DrawPois::transform
     */
    public function testDoesNotModifyImageIfNoPoiMetadataKeyIsNotAnArray() {
        $image = $this->getMock('Imbo\Model\Image');
        $database = $this->getMock('Imbo\Database\DatabaseInterface');
        $database->expects($this->once())->method('getMetadata')->will($this->returnValue(['poi' => 'wat']));

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->any())->method('getDatabase')->will($this->returnValue($database));

        $image->expects($this->never())->method('hasBeenTransformed');

        $transformation = new DrawPois();
        $transformation->transform($event);
    }

    /**
     * @covers Imbo\Image\Transformation\DrawPois::transform
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionMessage Point of interest had neither `width` and `height` nor `cx` and `cy`
     */
    public function testThrowsExceptionOnInvalidPoi() {
        $image = $this->getMock('Imbo\Model\Image');
        $database = $this->getMock('Imbo\Database\DatabaseInterface');
        $database->expects($this->once())->method('getMetadata')->will($this->returnValue([
            'poi' => [['foo' => 'bar']]
        ]));

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->any())->method('getDatabase')->will($this->returnValue($database));

        $image->expects($this->never())->method('hasBeenTransformed');

        $transformation = new DrawPois();
        $transformation->transform($event);
    }

    /**
     * @covers Imbo\Image\Transformation\DrawPois::transform
     */
    public function testDrawsSameAmountOfTimesAsPoisArePresent() {
        $image = $this->getMock('Imbo\Model\Image');
        $database = $this->getMock('Imbo\Database\DatabaseInterface');
        $database->expects($this->once())->method('getMetadata')->will($this->returnValue([
            'poi' => [[
                'x' => 362,
                'y' => 80,
                'cx' => 467,
                'cy' => 203,
                'width' => 210,
                'height' => 245
            ], [
                'x' => 74,
                'y' => 237,
                'cx' => 98,
                'cy' => 263,
                'width' => 48,
                'height' => 51
            ], [
                'cx' => 653,
                'cy' => 185
            ]]
        ]));

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->any())->method('getDatabase')->will($this->returnValue($database));

        $image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $imagick = $this->getMock('Imagick');
        $imagick->expects($this->exactly(3))->method('drawImage');

        $transformation = new DrawPois();
        $transformation->setImagick($imagick);
        $transformation->transform($event);
    }

    /**
     * @covers Imbo\Image\Transformation\DrawPois::transform
     */
    public function testDrawPois() {
        return;
        $imagick = $this->getMock('Imagick');
        $imagick->expects($this->any())
                ->method('cropImage')
                ->with(
                    $cropParams['width'],
                    $cropParams['height'],
                    $cropParams['left'],
                    $cropParams['top']
                );

        $image = new Image();
        $image->setWidth($imageDimensions['width']);
        $image->setHeight($imageDimensions['height']);

        $response = new Response();

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue($params));
        $event->expects($this->at(2))->method('getResponse')->will($this->returnValue($response));

        $transformation = new DrawPois();
        $transformation->setImagick($imagick);

        $transformation->transform($event);
    }
}
