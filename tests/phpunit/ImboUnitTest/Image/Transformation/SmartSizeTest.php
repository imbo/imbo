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

use Imbo\Image\Transformation\SmartSize;
use Imbo\Model\Image;

/**
 * @covers Imbo\Image\Transformation\SmartSize
 * @group unit
 * @group transformations
 */
class SmartSizeTest extends \PHPUnit_Framework_TestCase {
    /**
     * Fetch different invalid image parameter arrays
     *
     * @return array[]
     */
    public function getInvalidImageParams() {
        return [
            'Missing both width and height' => [
                [],
                400,
                'Both width and height needs to be specified'
            ],

            'Missing width' => [
                ['height' => 1337],
                400,
                'Both width and height needs to be specified'
            ],

            'Missing height' => [
                ['width' => 1337],
                400,
                'Both width and height needs to be specified'
            ],

            'Missing poi and aoi' => [
                ['width' => 1337, 'height' => 1337],
                400,
                'Either a point-of-interest (poi=x,y) or an area-of-interest (aoi=[w,h,x,y]) needs to be specified'
            ],
        ];
    }

    /**
     * @covers Imbo\Image\Transformation\SmartSize::transform
     * @dataProvider getInvalidImageParams
     */
    public function testThrowsExceptionWhenWidthIsMissing($params, $expectedExceptionCode, $expectedExceptionMessage) {
        $this->setExpectedException(
          'Imbo\Exception\TransformationException',
          $expectedExceptionMessage,
          $expectedExceptionCode
        );

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue(new Image()));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue($params));

        $transformation = new SmartSize();
        $transformation->transform($event);
    }

    /**
     * Fetch different valid smart size crop arguments and expected results
     *
     * @return array
     */
    public function getSmartSizeArguments() {
        return [
            'Square crop, (800,300) poi on landscape image' => [
                ['width' => 1200, 'height' => 700],
                ['width' => 400, 'height' => 400, 'poi' => '800,300'],
                ['width' => 700, 'height' => 700, 'left' => 450, 'top' => 0]
            ],

            'Square crop, (0,0) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,0'],
                ['width' => 700, 'height' => 700, 'left' => 0, 'top' => 0]
            ],

            'Square crop, (0,700) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,700'],
                ['width' => 700, 'height' => 700, 'left' => 0, 'top' => 350]
            ],

            'Square crop, (500,500) poi on portrait image' => [
                ['width' => 1200, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '500,500'],
                ['width' => 1200, 'height' => 1200, 'left' => 0, 'top' => 0]
            ]
        ];
    }

    /**
     * @covers Imbo\Image\Transformation\SmartSize::transform
     * @dataProvider getSmartSizeArguments
     */
    public function testSmartSize($imageDimensions, $params, $cropParams) {
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

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue($params));

        $transformation = new SmartSize();
        $transformation->setImagick($imagick);

        $transformation->transform($event);
    }
}
