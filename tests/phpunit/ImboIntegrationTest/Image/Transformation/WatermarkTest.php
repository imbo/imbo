<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Watermark,
    Imbo\Model\Image,
    Imbo\EventManager\Event,
    Imagick;

/**
 * @covers Imbo\Image\Transformation\Watermark
 * @group integration
 * @group transformations
 */
class WatermarkTest extends TransformationTests {
     /**
      * @var int
      */
    private $width = 500;

    /**
     * @var int
     */
    private $height = 500;

    /**
     * @var string
     */
    private $watermarkImg = 'f5f7851c40e2b76a01af9482f67bbf3f';

    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Watermark();
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getParamsForWatermarks() {
        return [
            'top left with default watermark and width' => [
                [
                    'width' => 200,
                ],
                [
                    'top left corner' => ['x' => 0, 'y' => 0, 'colors' => [0, 0, 0]],
                    'top right corner' => ['x' => $this->width - 1, 'y' => 0, 'colors' => [255, 255, 255]],
                    'bottom left corner' => ['x' => 0, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'bottom right corner' => ['x' => $this->width - 1, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'edge of watermark (inside)' => ['x' => 199, 'y' => 0, 'colors' => [0, 0, 0]],
                    'edge of watermark (outside)' => ['x' => 200, 'y' => 0, 'colors' => [255, 255, 255]],
                ],
            ],
            'top left with default watermark and height' => [
                [
                    'height' => 200,
                ],
                [
                    'top left corner' => ['x' => 0, 'y' => 0, 'colors' => [0, 0, 0]],
                    'top right corner' => ['x' => $this->width - 1, 'y' => 0, 'colors' => [255, 255, 255]],
                    'bottom left corner' => ['x' => 0, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'bottom right corner' => ['x' => $this->width - 1, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'edge of watermark (inside)' => ['x' => 199, 'y' => 0, 'colors' => [0, 0, 0]],
                    'edge of watermark (outside)' => ['x' => 200, 'y' => 0, 'colors' => [255, 255, 255]],
                ],
            ],
            'top right with default watermark and width' => [
                [
                    'width' => 200,
                    'position' => 'top-right',
                ],
                [
                    'top left corner' => ['x' => 0, 'y' => 0, 'colors' => [255, 255, 255]],
                    'top right corner' => ['x' => $this->width - 1, 'y' => 0, 'colors' => [0, 0, 0]],
                    'bottom left corner' => ['x' => 0, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'bottom right corner' => ['x' => $this->width - 1, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'edge of watermark (inside)' => ['x' => $this->width - 200, 'y' => 0, 'colors' => [0, 0, 0]],
                    'edge of watermark (outside)' => ['x' => $this->width - 201, 'y' => 0, 'colors' => [255, 255, 255]],
                ],
            ],
            'top right with default watermark and height' => [
                [
                    'height' => 200,
                    'position' => 'top-right',
                ],
                [
                    'top left corner' => ['x' => 0, 'y' => 0, 'colors' => [255, 255, 255]],
                    'top right corner' => ['x' => $this->width - 1, 'y' => 0, 'colors' => [0, 0, 0]],
                    'bottom left corner' => ['x' => 0, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'bottom right corner' => ['x' => $this->width - 1, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'edge of watermark (inside)' => ['x' => $this->width - 200, 'y' => 0, 'colors' => [0, 0, 0]],
                    'edge of watermark (outside)' => ['x' => $this->width - 201, 'y' => 0, 'colors' => [255, 255, 255]],
                ],
            ],
            'bottom left with default watermark and width' => [
                [
                    'width' => 200,
                    'position' => 'bottom-left',
                ],
                [
                    'top left corner' => ['x' => 0, 'y' => 0, 'colors' => [255, 255, 255]],
                    'top right corner' => ['x' => $this->width - 1, 'y' => 0, 'colors' => [255, 255, 255]],
                    'bottom left corner' => ['x' => 0, 'y' => $this->height - 1, 'colors' => [0, 0, 0]],
                    'bottom right corner' => ['x' => $this->width - 1, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'edge of watermark (inside)' => ['x' => 199, 'y' => $this->height - 1, 'colors' => [0, 0, 0]],
                    'edge of watermark (outside)' => ['x' => 200, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                ],
            ],
            'bottom left with default watermark and height' => [
                [
                    'height' => 200,
                    'position' => 'bottom-left',
                ],
                [
                    'top left corner' => ['x' => 0, 'y' => 0, 'colors' => [255, 255, 255]],
                    'top right corner' => ['x' => $this->width - 1, 'y' => 0, 'colors' => [255, 255, 255]],
                    'bottom left corner' => ['x' => 0, 'y' => $this->height - 1, 'colors' => [0, 0, 0]],
                    'bottom right corner' => ['x' => $this->width - 1, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'edge of watermark (inside)' => ['x' => 199, 'y' => $this->height - 1, 'colors' => [0, 0, 0]],
                    'edge of watermark (outside)' => ['x' => 200, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                ],
            ],
            'bottom right with default watermark and width' => [
                [
                    'width' => 200,
                    'position' => 'bottom-right',
                ],
                [
                    'top left corner' => ['x' => 0, 'y' => 0, 'colors' => [255, 255, 255]],
                    'top right corner' => ['x' => $this->width - 1, 'y' => 0, 'colors' => [255, 255, 255]],
                    'bottom left corner' => ['x' => 0, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'bottom right corner' => ['x' => $this->width - 1, 'y' => $this->height - 1, 'colors' => [0, 0, 0]],
                    'edge of watermark (inside)' => ['x' => $this->width - 200, 'y' => $this->height - 1, 'colors' => [0, 0, 0]],
                    'edge of watermark (outside)' => ['x' => $this->width - 201, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                ],
            ],
            'bottom right with default watermark and height' => [
                [
                    'height' => 200,
                    'position' => 'bottom-right',
                ],
                [
                    'top left corner' => ['x' => 0, 'y' => 0, 'colors' => [255, 255, 255]],
                    'top right corner' => ['x' => $this->width - 1, 'y' => 0, 'colors' => [255, 255, 255]],
                    'bottom left corner' => ['x' => 0, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'bottom right corner' => ['x' => $this->width - 1, 'y' => $this->height - 1, 'colors' => [0, 0, 0]],
                    'edge of watermark (inside)' => ['x' => $this->width - 200, 'y' => $this->height - 1, 'colors' => [0, 0, 0]],
                    'edge of watermark (outside)' => ['x' => $this->width - 201, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                ],
            ],
            'center position' => [
                [
                    'width' => 50,
                    'height' => 50,
                    'position' => 'center',
                ],
                [
                    'top left corner' => ['x' => 0, 'y' => 0, 'colors' => [255, 255, 255]],
                    'top right corner' => ['x' => $this->width - 1, 'y' => 0, 'colors' => [255, 255, 255]],
                    'bottom left corner' => ['x' => 0, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'bottom right corner' => ['x' => $this->width - 1, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'left edge of watermark (inside)' => ['x' => 225, 'y' => 225, 'colors' => [0, 0, 0]],
                    'left edge of watermark (outside)' => ['x' => 224, 'y' => 225, 'colors' => [255, 255, 255]],
                    'right edge of watermark (inside)' => ['x' => 274, 'y' => 225, 'colors' => [0, 0, 0]],
                    'right edge of watermark (outside)' => ['x' => 275, 'y' => 225, 'colors' => [255, 255, 255]],
                    'top edge of watermark (inside)' => ['x' => 225, 'y' => 225, 'colors' => [0, 0, 0]],
                    'top edge of watermark (outside)' => ['x' => 225, 'y' => 224, 'colors' => [255, 255, 255]],
                    'bottom edge of watermark (inside)' => ['x' => 225, 'y' => 274, 'colors' => [0, 0, 0]],
                    'bottom edge of watermark (outside)' => ['x' => 225, 'y' => 275, 'colors' => [255, 255, 255]],
                ],
            ],
            'offset' => [
                [
                    'position' => 'top-left',
                    'x' => 1,
                    'y' => 1,
                ],
                [
                    'top left corner' => ['x' => 0, 'y' => 0, 'colors' => [255, 255, 255]],
                    'top right corner' => ['x' => $this->width - 1, 'y' => 0, 'colors' => [255, 255, 255]],
                    'bottom left corner' => ['x' => 0, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'bottom right corner' => ['x' => $this->width - 1, 'y' => $this->height - 1, 'colors' => [255, 255, 255]],
                    'left edge of watermark (inside)' => ['x' => 1, 'y' => 1, 'colors' => [0, 0, 0]],
                    'left edge of watermark (outside)' => ['x' => 0, 'y' => 1, 'colors' => [255, 255, 255]],
                    'right edge of watermark (inside)' => ['x' => 100, 'y' => 1, 'colors' => [0, 0, 0]],
                    'right edge of watermark (outside)' => ['x' => 101, 'y' => 1, 'colors' => [255, 255, 255]],
                    'top edge of watermark (inside)' => ['x' => 1, 'y' => 1, 'colors' => [0, 0, 0]],
                    'top edge of watermark (outside)' => ['x' => 0, 'y' => 1, 'colors' => [255, 255, 255]],
                    'bottom edge of watermark (inside)' => ['x' => 1, 'y' => 100, 'colors' => [0, 0, 0]],
                    'bottom edge of watermark (outside)' => ['x' => 1, 'y' => 101, 'colors' => [255, 255, 255]],
                ],
            ],
            'opacity' => [
                [
                    'opacity' => 40
                ],
                [
                    'top left corner' => ['x' => 0, 'y' => 0, 'colors' => [153, 153, 153]], // 255*0.6=153
                    'top right corner' => ['x' => $this->width - 1, 'y' => 0, 'colors' => [255, 255, 255]],
                ]
            ]
        ];
    }

    /**
     * @dataProvider getParamsForWatermarks
     * @covers Imbo\Image\Transformation\Watermark::transform
     */
    public function testApplyToImageTopLeftWithOnlyWidthAndDefaultWatermark($params, $colors) {
        $blob = file_get_contents(FIXTURES_DIR . '/white.png');

        $image = new Image();
        $image->setBlob($blob);
        $image->setWidth($this->width);
        $image->setHeight($this->height);

        $transformation = $this->getTransformation();
        $transformation->setDefaultImage($this->watermarkImg);

        $expectedWatermark = $this->watermarkImg;

        if (isset($params['img'])) {
            $expectedWatermark = $params['img'];
        }

        $storage = $this->getMock('Imbo\Storage\StorageInterface');
        $storage->expects($this->once())
                ->method('getImage')
                ->with('someUser', $expectedWatermark)
                ->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/black.png')));

        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getUser')->will($this->returnValue('someUser'));

        $event = new Event();
        $event->setArguments([
            'image' => $image,
            'params' => $params,
            'storage' => $storage,
            'request' => $request,
        ]);

        $imagick = new Imagick();
        $imagick->readImageBlob($blob);

        $transformation->setImagick($imagick)->transform($event);

        foreach ($colors as $c) {
            $this->verifyColor($imagick, $c['x'], $c['y'], $c['colors']);
        }
    }

    /**
     * Verifies that the given image has a pixel with the given color value at the given position
     *
     * @param Imagick $imagick The imagick instance to verify
     * @param integer $x X position to check
     * @param integer $y Y position to check
     * @param array $expectedRgb Expected color value, in RGB format, as array
     */
    protected function verifyColor(Imagick $imagick, $x, $y, $expectedRgb) {
        // Do assertion comparison on the color values
        $pixelValue = $imagick->getImagePixelColor($x, $y)->getColorAsString();

        $this->assertStringEndsWith('rgb(' . implode(',', $expectedRgb) . ')', $pixelValue);
    }
}
