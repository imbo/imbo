<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imbo\Model\Image;
use Imbo\Storage\StorageInterface;
use Imbo\EventManager\Event;
use Imbo\Http\Request\Request;
use Imbo\Exception\StorageException;
use Imbo\Exception\TransformationException;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Watermark
 */
class WatermarkTest extends TransformationTests {
    private $transformation;
    private $width = 500;
    private $height = 500;
    private $watermarkImg = 'f5f7851c40e2b76a01af9482f67bbf3f';

    protected function getTransformation() : Watermark {
        return new Watermark();
    }

    public function setUp() : void {
        $this->transformation = new Watermark();
    }

    /**
     * @covers ::transform
     */
    public function testTransformThrowsExceptionIfNoImageSpecified() : void {
        $image = $this->createMock(Image::class);
        $this->expectExceptionObject(new TransformationException(
            'You must specify an image identifier to use for the watermark',
            400
        ));
        $this->transformation->setImage($image)->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testThrowsExceptionIfSpecifiedImageIsNotFound() : void {
        $e = new StorageException('File not found', 404);

        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('getImage')
            ->with('someuser', 'foobar')
            ->willThrowException($e);

        $request = $this->createConfiguredMock(Request::class, [
            'getUser' => 'someuser'
        ]);

        $event = $this->createConfiguredMock(Event::class, [
            'getStorage' => $storage,
            'getRequest' => $request,
        ]);

        $this->transformation
            ->setImage($this->createMock(Image::class))
            ->setEvent($event);

        $this->expectExceptionObject(new TransformationException(
            'Watermark image not found',
            400
        ));

        $this->transformation->transform(['img' => 'foobar']);
    }

    public function getParamsForWatermarks() : array {
        $black = [0, 0, 0];
        $white = [255, 255, 255];

        return [
            'top left (default) with default watermark' => [
                [
                    'width' => 200,
                    'height' => 100,
                ],
                [
                    'top left corner'                  => ['x' => 0,   'y' => 0,   'color' => $black],
                    'top right corner'                 => ['x' => 499, 'y' => 0,   'color' => $white],
                    'bottom left corner'               => ['x' => 0,   'y' => 499, 'color' => $white],
                    'bottom right corner'              => ['x' => 499, 'y' => 499, 'color' => $white],

                    'inside watermark (top left)'      => ['x' => 0,   'y' => 0,   'color' => $black],
                    'inside watermark (top right)'     => ['x' => 199, 'y' => 0,   'color' => $black],
                    'inside watermark (bottom left)'   => ['x' => 0,   'y' => 99,  'color' => $black],
                    'inside watermark (bottom right)'  => ['x' => 199, 'y' => 99,  'color' => $black],

                    'outside watermark (right top)'    => ['x' => 200, 'y' => 0,   'color' => $white],
                    'outside watermark (right bottom)' => ['x' => 200, 'y' => 99,  'color' => $white],
                    'outside watermark (bottom left)'  => ['x' => 0,   'y' => 100, 'color' => $white],
                    'outside watermark (bottom right)' => ['x' => 199, 'y' => 100, 'color' => $white],
                ],
            ],
            'top with default watermark' => [
                [
                    'width' => 200,
                    'height' => 100,
                    'position' => 'top',
                ],
                [
                    'top left corner'                  => ['x' => 0,   'y' => 0,   'color' => $white],
                    'top right corner'                 => ['x' => 499, 'y' => 0,   'color' => $white],
                    'bottom left corner'               => ['x' => 0,   'y' => 499, 'color' => $white],
                    'bottom right corner'              => ['x' => 499, 'y' => 499, 'color' => $white],

                    'inside watermark (top left)'      => ['x' => 150, 'y' => 0,   'color' => $black],
                    'inside watermark (top right)'     => ['x' => 349, 'y' => 0,   'color' => $black],
                    'inside watermark (bottom left)'   => ['x' => 150, 'y' => 99,  'color' => $black],
                    'inside watermark (bottom right)'  => ['x' => 349, 'y' => 99,  'color' => $black],

                    'outside watermark (left top)'     => ['x' => 149, 'y' => 0,   'color' => $white],
                    'outside watermark (left bottom)'  => ['x' => 149, 'y' => 100, 'color' => $white],
                    'outside watermark (bottom left)'  => ['x' => 150, 'y' => 100, 'color' => $white],
                    'outside watermark (bottom right)' => ['x' => 349, 'y' => 100, 'color' => $white],
                    'outside watermark (right bottom)' => ['x' => 350, 'y' => 100, 'color' => $white],
                    'outside watermark (right top)'    => ['x' => 350, 'y' => 0,   'color' => $white],
                ],
            ],
            'top right with default watermark' => [
                [
                    'width' => 200,
                    'height' => 100,
                    'position' => 'top-right',
                ],
                [
                    'top left corner'                  => ['x' => 0,   'y' => 0,   'color' => $white],
                    'top right corner'                 => ['x' => 499, 'y' => 0,   'color' => $black],
                    'bottom left corner'               => ['x' => 0,   'y' => 499, 'color' => $white],
                    'bottom right corner'              => ['x' => 499, 'y' => 499, 'color' => $white],

                    'inside watermark (top left)'      => ['x' => 300, 'y' => 0,   'color' => $black],
                    'inside watermark (top right)'     => ['x' => 499, 'y' => 0,   'color' => $black],
                    'inside watermark (bottom left)'   => ['x' => 300, 'y' => 99,  'color' => $black],
                    'inside watermark (bottom right)'  => ['x' => 499, 'y' => 99,  'color' => $black],

                    'outside watermark (left top)'     => ['x' => 299, 'y' => 0,   'color' => $white],
                    'outside watermark (left bottom)'  => ['x' => 299, 'y' => 99,  'color' => $white],
                    'outside watermark (bottom left)'  => ['x' => 300, 'y' => 100, 'color' => $white],
                    'outside watermark (bottom right)' => ['x' => 499, 'y' => 100, 'color' => $white],
                ],
            ],
            'left with default watermark' => [
                [
                    'width' => 200,
                    'height' => 100,
                    'position' => 'left',
                ],
                [
                    'top left corner'                  => ['x' => 0,   'y' => 0,   'color' => $white],
                    'top right corner'                 => ['x' => 499, 'y' => 0,   'color' => $white],
                    'bottom left corner'               => ['x' => 0,   'y' => 499, 'color' => $white],
                    'bottom right corner'              => ['x' => 499, 'y' => 499, 'color' => $white],

                    'inside watermark (top left)'      => ['x' => 0,   'y' => 200, 'color' => $black],
                    'inside watermark (top right)'     => ['x' => 199, 'y' => 200, 'color' => $black],
                    'inside watermark (bottom left)'   => ['x' => 0,   'y' => 299, 'color' => $black],
                    'inside watermark (bottom right)'  => ['x' => 199, 'y' => 299, 'color' => $black],

                    'outside watermark (top left)'     => ['x' => 0,   'y' => 199, 'color' => $white],
                    'outside watermark (top right)'    => ['x' => 199, 'y' => 199, 'color' => $white],
                    'outside watermark (right top)'    => ['x' => 200, 'y' => 200, 'color' => $white],
                    'outside watermark (right bottom)' => ['x' => 200, 'y' => 299, 'color' => $white],
                    'outside watermark (bottom right)' => ['x' => 199, 'y' => 300, 'color' => $white],
                    'outside watermark (bottom left)'  => ['x' => 0,   'y' => 300, 'color' => $white],
                ],
            ],
            'center with default watermark' => [
                [
                    'width' => 200,
                    'height' => 100,
                    'position' => 'center',
                ],
                [
                    'top left corner'                  => ['x' => 0,   'y' => 0,   'color' => $white],
                    'top right corner'                 => ['x' => 499, 'y' => 0,   'color' => $white],
                    'bottom left corner'               => ['x' => 0,   'y' => 499, 'color' => $white],
                    'bottom right corner'              => ['x' => 499, 'y' => 499, 'color' => $white],

                    'inside watermark (top left)'      => ['x' => 150, 'y' => 200, 'color' => $black],
                    'inside watermark (top right)'     => ['x' => 349, 'y' => 200, 'color' => $black],
                    'inside watermark (bottom left)'   => ['x' => 150, 'y' => 299, 'color' => $black],
                    'inside watermark (bottom right)'  => ['x' => 349, 'y' => 299, 'color' => $black],

                    'outside watermark (top left)'     => ['x' => 150, 'y' => 199, 'color' => $white],
                    'outside watermark (top right)'    => ['x' => 349, 'y' => 199, 'color' => $white],
                    'outside watermark (right top)'    => ['x' => 350, 'y' => 200, 'color' => $white],
                    'outside watermark (right bottom)' => ['x' => 350, 'y' => 299, 'color' => $white],
                    'outside watermark (bottom right)' => ['x' => 349, 'y' => 300, 'color' => $white],
                    'outside watermark (bottom left)'  => ['x' => 150, 'y' => 300, 'color' => $white],
                    'outside watermark (left bottom)'  => ['x' => 149, 'y' => 299, 'color' => $white],
                    'outside watermark (left top)'     => ['x' => 149, 'y' => 299, 'color' => $white],
                ],
            ],
            'right with default watermark' => [
                [
                    'width' => 200,
                    'height' => 100,
                    'position' => 'right',
                ],
                [
                    'top left corner'                  => ['x' => 0,   'y' => 0,   'color' => $white],
                    'top right corner'                 => ['x' => 499, 'y' => 0,   'color' => $white],
                    'bottom left corner'               => ['x' => 0,   'y' => 499, 'color' => $white],
                    'bottom right corner'              => ['x' => 499, 'y' => 499, 'color' => $white],

                    'inside watermark (top left)'      => ['x' => 300, 'y' => 200, 'color' => $black],
                    'inside watermark (top right)'     => ['x' => 499, 'y' => 200, 'color' => $black],
                    'inside watermark (bottom left)'   => ['x' => 300, 'y' => 299, 'color' => $black],
                    'inside watermark (bottom right)'  => ['x' => 499, 'y' => 299, 'color' => $black],

                    'outside watermark (top left)'     => ['x' => 300, 'y' => 199, 'color' => $white],
                    'outside watermark (top right)'    => ['x' => 499, 'y' => 199, 'color' => $white],
                    'outside watermark (left top)'     => ['x' => 299, 'y' => 200, 'color' => $white],
                    'outside watermark (left bottom)'  => ['x' => 299, 'y' => 299, 'color' => $white],
                    'outside watermark (bottom left)'  => ['x' => 300, 'y' => 300, 'color' => $white],
                    'outside watermark (bottom right)' => ['x' => 499, 'y' => 300, 'color' => $white],
                ],
            ],
            'bottom left with default watermark' => [
                [
                    'width' => 200,
                    'height' => 100,
                    'position' => 'bottom-left',
                ],
                [
                    'top left corner'                  => ['x' => 0,   'y' => 0,   'color' => $white],
                    'top right corner'                 => ['x' => 499, 'y' => 0,   'color' => $white],
                    'bottom left corner'               => ['x' => 0,   'y' => 499, 'color' => $black],
                    'bottom right corner'              => ['x' => 499, 'y' => 499, 'color' => $white],

                    'inside watermark (top left)'      => ['x' => 0,   'y' => 400, 'color' => $black],
                    'inside watermark (top right)'     => ['x' => 199, 'y' => 400, 'color' => $black],
                    'inside watermark (bottom left)'   => ['x' => 0,   'y' => 499, 'color' => $black],
                    'inside watermark (bottom right)'  => ['x' => 199, 'y' => 499, 'color' => $black],

                    'outside watermark (top left)'     => ['x' => 0,   'y' => 399, 'color' => $white],
                    'outside watermark (top right)'    => ['x' => 199, 'y' => 399, 'color' => $white],
                    'outside watermark (right top)'    => ['x' => 200, 'y' => 400, 'color' => $white],
                    'outside watermark (right bottom)' => ['x' => 200, 'y' => 499, 'color' => $white],
                ],
            ],
            'bottom with default watermark' => [
                [
                    'width' => 200,
                    'height' => 100,
                    'position' => 'bottom',
                ],
                [
                    'top left corner'                  => ['x' => 0,   'y' => 0,   'color' => $white],
                    'top right corner'                 => ['x' => 499, 'y' => 0,   'color' => $white],
                    'bottom left corner'               => ['x' => 0,   'y' => 499, 'color' => $white],
                    'bottom right corner'              => ['x' => 499, 'y' => 499, 'color' => $white],

                    'inside watermark (top left)'      => ['x' => 150, 'y' => 400, 'color' => $black],
                    'inside watermark (top right)'     => ['x' => 349, 'y' => 400, 'color' => $black],
                    'inside watermark (bottom left)'   => ['x' => 150, 'y' => 499, 'color' => $black],
                    'inside watermark (bottom right)'  => ['x' => 349, 'y' => 499, 'color' => $black],

                    'outside watermark (top left)'     => ['x' => 150, 'y' => 399, 'color' => $white],
                    'outside watermark (top right)'    => ['x' => 349, 'y' => 399, 'color' => $white],
                    'outside watermark (right top)'    => ['x' => 350, 'y' => 400, 'color' => $white],
                    'outside watermark (right bottom)' => ['x' => 350, 'y' => 499, 'color' => $white],
                    'outside watermark (left bottom)'  => ['x' => 149, 'y' => 499, 'color' => $white],
                    'outside watermark (left top)'     => ['x' => 149, 'y' => 499, 'color' => $white],
                ],
            ],
            'bottom right with default watermark' => [
                [
                    'width' => 200,
                    'height' => 100,
                    'position' => 'bottom-right',
                ],
                [
                    'top left corner'                  => ['x' => 0,   'y' => 0,   'color' => $white],
                    'top right corner'                 => ['x' => 499, 'y' => 0,   'color' => $white],
                    'bottom left corner'               => ['x' => 0,   'y' => 499, 'color' => $white],
                    'bottom right corner'              => ['x' => 499, 'y' => 499, 'color' => $black],

                    'inside watermark (top left)'      => ['x' => 300, 'y' => 400, 'color' => $black],
                    'inside watermark (top right)'     => ['x' => 499, 'y' => 400, 'color' => $black],
                    'inside watermark (bottom left)'   => ['x' => 300, 'y' => 499, 'color' => $black],
                    'inside watermark (bottom right)'  => ['x' => 499, 'y' => 499, 'color' => $black],

                    'outside watermark (top left)'     => ['x' => 300, 'y' => 399, 'color' => $white],
                    'outside watermark (top right)'    => ['x' => 499, 'y' => 399, 'color' => $white],
                    'outside watermark (left top)'     => ['x' => 299, 'y' => 400, 'color' => $white],
                    'outside watermark (left bottom)'  => ['x' => 299, 'y' => 499, 'color' => $white],
                ],
            ],

            'offset' => [
                [
                    'position' => 'top-left',
                    'x' => 1,
                    'y' => 1,
                ],
                [
                    'top left corner'                    => ['x' => 0,   'y' => 0,   'color' => $white],
                    'top right corner'                   => ['x' => 499, 'y' => 0,   'color' => $white],
                    'bottom left corner'                 => ['x' => 0,   'y' => 499, 'color' => $white],
                    'bottom right corner'                => ['x' => 499, 'y' => 499, 'color' => $white],
                    'left edge of watermark (inside)'    => ['x' => 1,   'y' => 1,   'color' => $black],
                    'left edge of watermark (outside)'   => ['x' => 0,   'y' => 1,   'color' => $white],
                    'right edge of watermark (inside)'   => ['x' => 100, 'y' => 1,   'color' => $black],
                    'right edge of watermark (outside)'  => ['x' => 101, 'y' => 1,   'color' => $white],
                    'top edge of watermark (inside)'     => ['x' => 1,   'y' => 1,   'color' => $black],
                    'top edge of watermark (outside)'    => ['x' => 0,   'y' => 1,   'color' => $white],
                    'bottom edge of watermark (inside)'  => ['x' => 1,   'y' => 100, 'color' => $black],
                    'bottom edge of watermark (outside)' => ['x' => 1,   'y' => 101, 'color' => $white],
                ],
            ],

            'opacity' => [
                [
                    'opacity' => 40
                ],
                [
                    'top left corner'  => ['x' => 0,   'y' => 0, 'color' => [153, 153, 153]], // 255 * 0.6 = 153
                    'top right corner' => ['x' => 499, 'y' => 0, 'color' => $white],
                ]
            ],
            'alpha' => [
                [
                    'watermarkFixture' => 'black-alpha.png',
                ],
                [
                    'top left corner'   => ['x' => 0,  'y' => 0,  'color' => $white],
                    'top mid watermark' => ['x' => 50, 'y' => 50, 'color' => $black],
                ],
            ],
            'alpha with opacity' => [
                [
                    'opacity' => 40,
                    'watermarkFixture' => 'black-alpha.png',
                ],
                [
                    'top left corner'   => ['x' => 0,  'y' => 0,  'color' => $white],
                    'top mid watermark' => ['x' => 50, 'y' => 50, 'color' => [153, 153, 153]], // 255 * 0.6 = 153
                ],
            ],
            'jpg with opacity' => [
                [
                    'opacity' => 40,
                    'watermarkFixture' => 'black.jpg',
                ],
                [
                    'top left corner'  => ['x' => 0,   'y' => 0, 'color' => [153, 153, 153]], // 255 * 0.6 = 153
                    'top right corner' => ['x' => 499, 'y' => 0, 'color' => $white],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getParamsForWatermarks
     * @covers ::transform
     * @covers ::setDefaultImage
     */
    public function testApplyToImageTopLeftWithOnlyWidthAndDefaultWatermark(array $params, array $colors) : void {
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

        $watermarkFixture = 'black.png';

        if (isset($params['watermarkFixture'])) {
            $watermarkFixture = $params['watermarkFixture'];
        }

        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('getImage')
            ->with('someUser', $expectedWatermark)
            ->willReturn(file_get_contents(FIXTURES_DIR . '/' . $watermarkFixture));

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getUser')
            ->willReturn('someUser');

        $event = new Event();
        $event->setArguments([
            'storage' => $storage,
            'request' => $request,
        ]);

        $imagick = new Imagick();
        $imagick->readImageBlob($blob);

        $transformation->setEvent($event)->setImage($image)->setImagick($imagick)->transform($params);

        foreach ($colors as $key => $c) {
            $this->verifyColor($imagick, $c['x'], $c['y'], $c['color'], $key);
        }
    }

    /**
     * Verifies that the given image has a pixel with the given color value at the given position
     *
     * @param Imagick $imagick The imagick instance to verify
     * @param int $x X position to check
     * @param int $y Y position to check
     * @param array $expectedRgb Expected color value, in RGB format, as array
     * @param string $key Name of the key from the colors array
     */
    protected function verifyColor(Imagick $imagick, int $x, int $y, array $expectedRgb, string $key) : void {
        // Do assertion comparison on the color values
        $pixelValue = $imagick->getImagePixelColor($x, $y)->getColorAsString();

        $this->assertStringEndsWith(
            $expected = 'rgb(' . implode(',', $expectedRgb) . ')',
            $actual = $pixelValue,
            sprintf('Color comparison for key "%s" failed. Expected "%s", got: "%s"', $key, $expected, $actual)
        );
    }
}
