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
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Image\Transformation\SmartSize
 * @group unit
 * @group transformations
 */
class SmartSizeTest extends TestCase {
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
                ['width' => 400, 'height' => 400, 'x' => 600, 'y' => 100]
            ],

            'Square, close crop, (0,0) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,0', 'crop' => 'close'],
                ['width' => 400, 'height' => 400, 'x' => 0, 'y' => 0]
            ],

            'Square, close crop, (0,700) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,700', 'crop' => 'close'],
                ['width' => 400, 'height' => 400, 'x' => 0, 'y' => 500]
            ],

            'Square, close crop, (500,500) poi on square image' => [
                ['width' => 1200, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '500,500', 'crop' => 'close'],
                ['width' => 400, 'height' => 400, 'x' => 300, 'y' => 300]
            ],

            'Portrait, close crop, (600,300) poi on landscape image' => [
                ['width' => 1200, 'height' => 600],
                ['width' => 400, 'height' => 700, 'poi' => '600,300', 'crop' => 'close'],
                ['width' => 343, 'height' => 600, 'x' => 429, 'y' => 0]
            ],

            'Panorama, close crop, (100,700) poi on portrait image' => [
                ['width' => 800, 'height' => 1800],
                ['width' => 80, 'height' => 30, 'poi' => '100,700', 'crop' => 'close'],
                ['width' => 240, 'height' => 90, 'x' => 0, 'y' => 655]
            ],

            'Square, medium crop, (800,300) poi on landscape image' => [
                ['width' => 1200, 'height' => 700],
                ['width' => 400, 'height' => 400, 'poi' => '800,300', 'crop' => 'medium'],
                ['width' => 500, 'height' => 500, 'x' => 550, 'y' => 50]
            ],

            'Square, medium crop, (0,0) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,0', 'crop' => 'medium'],
                ['width' => 500, 'height' => 500, 'x' => 0, 'y' => 0]
            ],

            'Square, medium crop, (0,700) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,700', 'crop' => 'medium'],
                ['width' => 500, 'height' => 500, 'x' => 0, 'y' => 450]
            ],

            'Square, medium crop, (500,500) poi on square image' => [
                ['width' => 1200, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '500,500', 'crop' => 'medium'],
                ['width' => 600, 'height' => 600, 'x' => 200, 'y' => 200]
            ],

            'Portrait, medium crop, (600,300) poi on landscape image' => [
                ['width' => 1200, 'height' => 600],
                ['width' => 400, 'height' => 700, 'poi' => '600,300', 'crop' => 'medium'],
                ['width' => 343, 'height' => 600, 'x' => 429, 'y' => 0]
            ],

            'Panorama, medium crop, (100,700) poi on portrait image' => [
                ['width' => 800, 'height' => 1800],
                ['width' => 800, 'height' => 300, 'poi' => '100,700', 'crop' => 'medium'],
                ['width' => 800, 'height' => 300, 'x' => 0, 'y' => 550]
            ],

            'Square, wide crop, (800,300) poi on landscape image' => [
                ['width' => 1200, 'height' => 700],
                ['width' => 400, 'height' => 400, 'poi' => '800,300', 'crop' => 'wide'],
                ['width' => 640, 'height' => 640, 'x' => 480, 'y' => 0]
            ],

            'Square, wide crop, (0,0) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,0', 'crop' => 'wide'],
                ['width' => 640, 'height' => 640, 'x' => 0, 'y' => 0]
            ],

            'Square, wide crop, (0,700) poi on portrait image' => [
                ['width' => 700, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '0,700', 'crop' => 'wide'],
                ['width' => 640, 'height' => 640, 'x' => 0, 'y' => 380]
            ],

            'Square, wide crop, (500,500) poi on square image' => [
                ['width' => 1200, 'height' => 1200],
                ['width' => 400, 'height' => 400, 'poi' => '500,500', 'crop' => 'wide'],
                ['width' => 792, 'height' => 792, 'x' => 104, 'y' => 104]
            ],

            'Portrait, wide crop, (600,300) poi on landscape image' => [
                ['width' => 1200, 'height' => 600],
                ['width' => 400, 'height' => 700, 'poi' => '600,300', 'crop' => 'wide'],
                ['width' => 343, 'height' => 600, 'x' => 429, 'y' => 0]
            ],

            'Panorama, wide crop, (100,700) poi on portrait image' => [
                ['width' => 800, 'height' => 1800],
                ['width' => 800, 'height' => 300, 'poi' => '100,700', 'crop' => 'wide'],
                ['width' => 800, 'height' => 300, 'x' => 0, 'y' => 550]
            ]
        ];
    }

    /**
     * @covers Imbo\Image\Transformation\SmartSize::transform
     * @dataProvider getSmartSizeArguments
     */
    public function testSmartSize($imageDimensions, $params, $cropParams) {
        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->any())
                ->method('cropImage')
                ->with(
                    $cropParams['width'],
                    $cropParams['height'],
                    $cropParams['x'],
                    $cropParams['y']
                );

        $image = new Image();
        $image->setWidth($imageDimensions['width']);
        $image->setHeight($imageDimensions['height']);

        $headerBag = $this->createMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $headerBag->expects($this->once())->method('set')->with('X-Imbo-POIs-Used', 1);

        $response = new Response();
        $response->headers = $headerBag;

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getResponse')->will($this->returnValue($response));

        $transformation = new SmartSize();
        $transformation
            ->setImagick($imagick)
            ->setImage($image)
            ->setEvent($event)
            ->transform($params);
    }
}
