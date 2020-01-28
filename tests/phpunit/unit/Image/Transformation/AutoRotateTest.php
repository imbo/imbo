<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imbo\Model\Image;
use Imbo\Image\InputSizeConstraint;
use Imbo\Exception\TransformationException;
use Imagick;
use ImagickException;

/**
 * @coversDefaultClass Imbo\Image\Transformation\AutoRotate
 */
class AutoRotateTest extends TransformationTests {
    protected function getTransformation() : AutoRotate {
        return new AutoRotate();
    }

    public function getFiles() : array {
        return [
            'orientation1.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation1.jpeg', false, false],
            'orientation2.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation2.jpeg', false, true],
            'orientation3.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation3.jpeg', false, true],
            'orientation4.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation4.jpeg', false, true],
            'orientation5.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation5.jpeg', true, true],
            'orientation6.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation6.jpeg', true, true],
            'orientation7.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation7.jpeg', true, true],
            'orientation8.jpeg' => [FIXTURES_DIR . '/autoRotate/orientation8.jpeg', true, true],
        ];
    }

    /**
     * @dataProvider getFiles
     */
    public function testAutoRotatesAllOrientations(string $file, bool $changeDimensions, bool $transformed) : void {
        $colorValues = [
            [
                'x' => 0,
                'y' => 0,
                'color' => 'rgb(128,63,193)'
            ],
            [
                'x' => 0,
                'y' => 1000,
                'color' => 'rgb(254,57,126)'
            ],
            [
                'x' => 1000,
                'y' => 0,
                'color' => 'rgb(127,131,194)'
            ],
            [
                'x' => 1000,
                'y' => 1000,
                'color' => 'rgb(249,124,192)'
            ],
        ];

        /**
         * Load the image, perform the auto rotate tranformation and check that the color codes in
         * the four corner pixels match the known color values as defined in $colorValues
         */
        $blob = file_get_contents($file);

        $image = $this->createMock(Image::class);

        if ($changeDimensions) {
            $image
                ->expects($this->once())
                ->method('setWidth')
                ->with(350)
                ->willReturn($image);

            $image
                ->expects($this->once())
                ->method('setHeight')
                ->with(350)
                ->willReturn($image);
        } else {
            $image
                ->expects($this->never())
                ->method('setWidth');

            $image
                ->expects($this->never())
                ->method('setHeight');
        }

        if ($transformed) {
            $image
                ->expects($this->once())
                ->method('hasBeenTransformed')
                ->with(true);
        } else {
            $image
                ->expects($this->never())
                ->method('hasBeenTransformed');
        }

        // Perform the auto rotate transformation on the image
        $imagick = new Imagick();
        $imagick->readImageBlob($blob);

        $this->getTransformation()
            ->setImagick($imagick)
            ->setImage($image)
            ->transform([]);

        // Do assertion comparison on the color values
        foreach ($colorValues as $pixelInfo) {
            $pixelValue = $imagick
                ->getImagePixelColor($pixelInfo['x'], $pixelInfo['y'])
                ->getColorAsString();

            $this->assertStringEndsWith($pixelInfo['color'], $pixelValue);
        }
    }

    /**
     * @covers ::transform
     */
    public function testWillNotUpdateTheImageWhenNotNeeded() : void {
        $imagick = $this->createConfiguredMock(Imagick::class, [
            'getImageOrientation' => 0,
        ]);
        $imagick
            ->expects($this->never())
            ->method('setImageOrientation');

        (new AutoRotate())
            ->setImagick($imagick)
            ->transform([]);
    }

    public function getTransformationData() : array {
        return [
            [
                Imagick::ORIENTATION_TOPRIGHT,
                Imagick::ORIENTATION_TOPLEFT,
                function(Imagick $imagick, Image $image) : void {
                    $imagick
                        ->expects($this->once())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->never())
                        ->method('flipImage');
                },
            ],
            [
                Imagick::ORIENTATION_BOTTOMRIGHT,
                Imagick::ORIENTATION_TOPLEFT,
                function(Imagick $imagick, Image $image) : void {
                    $imagick
                        ->expects($this->never())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->never())
                        ->method('flipImage');
                },
            ],
            [
                Imagick::ORIENTATION_BOTTOMLEFT,
                Imagick::ORIENTATION_TOPLEFT,
                function(Imagick $imagick, Image $image) : void {
                    $imagick
                        ->expects($this->never())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->once())
                        ->method('flipImage');
                },
            ],
            [
                Imagick::ORIENTATION_LEFTTOP,
                Imagick::ORIENTATION_TOPLEFT,
                function(Imagick $imagick, Image $image) : void {
                    $imagick
                        ->expects($this->once())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->never())
                        ->method('flipImage');
                    $imagick
                        ->expects($this->once())
                        ->method('getImageGeometry')
                        ->willReturn(['width' => 200, 'height' => 150]);

                    $image
                        ->expects($this->once())
                        ->method('setWidth')
                        ->with(200)
                        ->willReturnSelf();
                    $image
                        ->expects($this->once())
                        ->method('setHeight')
                        ->with(150)
                        ->willReturnSelf();
                },
            ],
            [
                Imagick::ORIENTATION_RIGHTTOP,
                Imagick::ORIENTATION_TOPLEFT,
                function(Imagick $imagick, Image $image) : void {
                    $imagick
                        ->expects($this->never())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->never())
                        ->method('flipImage');
                    $imagick
                        ->expects($this->once())
                        ->method('getImageGeometry')
                        ->willReturn(['width' => 200, 'height' => 150]);

                    $image
                        ->expects($this->once())
                        ->method('setWidth')
                        ->with(200)
                        ->willReturnSelf();
                    $image
                        ->expects($this->once())
                        ->method('setHeight')
                        ->with(150)
                        ->willReturnSelf();
                },
            ],
            [
                Imagick::ORIENTATION_RIGHTBOTTOM,
                Imagick::ORIENTATION_TOPLEFT,
                function(Imagick $imagick, Image $image) : void {
                    $imagick
                        ->expects($this->never())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->once())
                        ->method('flipImage');
                    $imagick
                        ->expects($this->once())
                        ->method('getImageGeometry')
                        ->willReturn(['width' => 200, 'height' => 150]);

                    $image
                        ->expects($this->once())
                        ->method('setWidth')
                        ->with(200)
                        ->willReturnSelf();
                    $image
                        ->expects($this->once())
                        ->method('setHeight')
                        ->with(150)
                        ->willReturnSelf();
                },
            ],
            [
                Imagick::ORIENTATION_LEFTBOTTOM,
                Imagick::ORIENTATION_TOPLEFT,
                function(Imagick $imagick, Image $image) : void {
                    $imagick
                        ->expects($this->never())
                        ->method('flopImage');
                    $imagick
                        ->expects($this->never())
                        ->method('flipImage');
                    $imagick
                        ->expects($this->once())
                        ->method('getImageGeometry')
                        ->willReturn(['width' => 200, 'height' => 150]);

                    $image
                        ->expects($this->once())
                        ->method('setWidth')
                        ->with(200)
                        ->willReturnSelf();
                    $image
                        ->expects($this->once())
                        ->method('setHeight')
                        ->with(150)
                        ->willReturnSelf();
                },
            ],
        ];
    }

    /**
     * @dataProvider getTransformationData
     * @covers ::transform
     */
    public function testWillRotateWhenNeeded(int $imageOrientation, int $newOrientation, callable $expectations) : void {
        $imagick = $this->createConfiguredMock(Imagick::class, [
            'getImageOrientation' => $imageOrientation,
        ]);

        $image = $this->createMock(Image::class);

        $expectations($imagick, $image);

        $imagick
            ->expects($this->once())
            ->method('setImageOrientation')
            ->with($newOrientation);

        (new AutoRotate())
            ->setImage($image)
            ->setImagick($imagick)
            ->transform([]);
    }

    /**
     * @covers ::getMinimumInputSize
     */
    public function testGetMinimumInputSizeStopsResolving() : void {
        $this->assertSame(InputSizeConstraint::STOP_RESOLVING, (new AutoRotate())->getMinimumInputSize([], []));
    }

    /**
     * @covers ::transform
     */
    public function testThrowsCustomExceptions() {
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('getImageOrientation')
            ->willThrowException($e = new ImagickException('some error'));

        $this->expectExceptionObject(new TransformationException('some error', 400, $e));

        (new AutoRotate())
            ->setImagick($imagick)
            ->transform([]);
    }
}
