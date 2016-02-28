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
use Imbo\Image\Transformation\SmartSize;
use Imbo\Model\Image;

/**
 * @covers Imbo\Image\Transformation\SmartSize
 * @group unit
 * @group transformations
 */
class SmartSizeTest extends \PHPUnit_Framework_TestCase {
    /**
     * Fetch different valid smart size crop arguments and expected results
     *
     * @return array
     */
    public function getSmartSizeArguments() {
        return [
            'Square, close crop, (800,300) poi on landscape image' => [
                ['width' => 1200, 'height' => 700],
                ['width' => 400, 'height' => 400, 'poi' => '800,300', 'crop' => 'close'],
                ['width' => 400, 'height' => 400, 'left' => 600, 'top' => 100]
            ],

            'Square, close crop, (0,0) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,0', 'crop' => 'close'],
                ['width' => 400, 'height' => 400, 'left' => 0, 'top' => 0]
            ],

            'Square, close crop, (0,700) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,700', 'crop' => 'close'],
                ['width' => 400, 'height' => 400, 'left' => 0, 'top' => 500]
            ],

            'Square, close crop, (500,500) poi on square image' => [
                ['width' => 1200, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '500,500', 'crop' => 'close'],
                ['width' => 400, 'height' => 400, 'left' => 300, 'top' => 300]
            ],

            'Portrait, close crop, (600,300) poi on landscape image' => [
                ['width' => 1200, 'height' => 600],
                ['width' => 400, 'height' => 700, 'poi' => '600,300', 'crop' => 'close'],
                ['width' => 343, 'height' => 600, 'left' => 429, 'top' => 0]
            ],

            'Panorama, close crop, (100,700) poi on portrait image' => [
                ['width' => 800, 'height' => 1800],
                ['width' => 80, 'height' => 30, 'poi' => '100,700', 'crop' => 'close'],
                ['width' => 240, 'height' => 90, 'left' => 0, 'top' => 655]
            ],

            'Square, medium crop, (800,300) poi on landscape image' => [
                ['width' => 1200, 'height' => 700],
                ['width' => 400, 'height' => 400, 'poi' => '800,300', 'crop' => 'medium'],
                ['width' => 500, 'height' => 500, 'left' => 550, 'top' => 50]
            ],

            'Square, medium crop, (0,0) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,0', 'crop' => 'medium'],
                ['width' => 500, 'height' => 500, 'left' => 0, 'top' => 0]
            ],

            'Square, medium crop, (0,700) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,700', 'crop' => 'medium'],
                ['width' => 500, 'height' => 500, 'left' => 0, 'top' => 450]
            ],

            'Square, medium crop, (500,500) poi on square image' => [
                ['width' => 1200, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '500,500', 'crop' => 'medium'],
                ['width' => 600, 'height' => 600, 'left' => 200, 'top' => 200]
            ],

            'Portrait, medium crop, (600,300) poi on landscape image' => [
                ['width' => 1200, 'height' => 600],
                ['width' => 400, 'height' => 700, 'poi' => '600,300', 'crop' => 'medium'],
                ['width' => 343, 'height' => 600, 'left' => 429, 'top' => 0]
            ],

            'Panorama, medium crop, (100,700) poi on portrait image' => [
                ['width' => 800, 'height' => 1800],
                ['width' => 800, 'height' => 300, 'poi' => '100,700', 'crop' => 'medium'],
                ['width' => 800, 'height' => 300, 'left' => 0, 'top' => 550]
            ],

            'Square, wide crop, (800,300) poi on landscape image' => [
                ['width' => 1200, 'height' => 700],
                ['width' => 400, 'height' => 400, 'poi' => '800,300', 'crop' => 'wide'],
                ['width' => 640, 'height' => 640, 'left' => 480, 'top' => 0]
            ],

            'Square, wide crop, (0,0) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,0', 'crop' => 'wide'],
                ['width' => 640, 'height' => 640, 'left' => 0, 'top' => 0]
            ],

            'Square, wide crop, (0,700) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,700', 'crop' => 'wide'],
                ['width' => 640, 'height' => 640, 'left' => 0, 'top' => 380]
            ],

            'Square, wide crop, (500,500) poi on square image' => [
                ['width' => 1200, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '500,500', 'crop' => 'wide'],
                ['width' => 792, 'height' => 792, 'left' => 104, 'top' => 104]
            ],

            'Portrait, wide crop, (600,300) poi on landscape image' => [
                ['width' => 1200, 'height' => 600],
                ['width' => 400, 'height' => 700, 'poi' => '600,300', 'crop' => 'wide'],
                ['width' => 343, 'height' => 600, 'left' => 429, 'top' => 0]
            ],

            'Panorama, wide crop, (100,700) poi on portrait image' => [
                ['width' => 800, 'height' => 1800],
                ['width' => 800, 'height' => 300, 'poi' => '100,700', 'crop' => 'wide'],
                ['width' => 800, 'height' => 300, 'left' => 0, 'top' => 550]
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

        $response = new Response();

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue($params));
        $event->expects($this->at(2))->method('getResponse')->will($this->returnValue($response));

        $transformation = new SmartSize();
        $transformation->setImagick($imagick);

        $transformation->transform($event);
    }
}
