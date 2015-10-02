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
     * Fetch different valid smart size crop arguments and expected results
     *
     * @return array
     */
    public function getSmartSizeArguments() {
        return [
            'Square crop, (800,300) poi on landscape image' => [
                ['width' => 1200, 'height' => 700],
                ['width' => 400, 'height' => 400, 'poi' => '800,300'],
                ['width' => 500, 'height' => 500, 'left' => 550, 'top' => 50]
            ],

            'Square crop, (0,0) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,0'],
                ['width' => 500, 'height' => 500, 'left' => 0, 'top' => 0]
            ],

            'Square crop, (0,700) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,700'],
                ['width' => 500, 'height' => 500, 'left' => 0, 'top' => 450]
            ],

            'Square crop, (500,500) poi on portrait image' => [
                ['width' => 1200, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '500,500'],
                ['width' => 500, 'height' => 500, 'left' => 250, 'top' => 250]
            ],

            'Portrait crop, (600,300) poi on landscape image' => [
                ['width' => 1200, 'height' => 600],
                ['width' => 400, 'height' => 700, 'poi' => '600,300'],
                ['width' => 343, 'height' => 600, 'left' => 429, 'top' => 0]
            ],

            'Panorame crop, (100,700) point on portrait image' => [
                ['width' => 800, 'height' => 1800],
                ['width' => 800, 'height' => 300, 'poi' => '100,700'],
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

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue($params));

        $transformation = new SmartSize();
        $transformation->setImagick($imagick);

        $transformation->transform($event);
    }
}
