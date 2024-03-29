<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use ImagickDraw;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

/**
 * Histogram transformation
 */
class Histogram extends Transformation
{
    /**
     * Generated histogram scale factor.
     *
     * The histogram will be generated as a 256px wide image, unless a different scale setting is
     * provided. The end image size will be 256px * scale factor. Use the resize/maxsize
     * transformations to generate a smaller version of the end image, which will then be
     * anti-aliased on resize.
     *
     * Only unsigned, positive integers are allowed.
     */
    private int $scale = 1;

    /**
     * Color to use when drawing the graph for the red channel.
     */
    private string $red = '#D93333';

    /**
     * Color to use when drawing the graph for the green channel.
     */
    private string $green = '#58C458';

    /**
     * Color to use when drawing the graph for the blue channel.
     */
    private string $blue = '#3767BF';

    /**
     * Ratio between width / height. Defaults to the golden ratio.
     */
    private float $ratio = 1.618;

    public function transform(array $params): void
    {
        $scale = !empty($params['scale']) ? max(1, min(8, (int) $params['scale'])) : $this->scale;
        $ratio = !empty($params['ratio']) ? max(0.1, min(8, (float) $params['ratio'])) : $this->ratio;

        // colors to use for each channel when drawing the histogram
        $colors = [
            'red' => !empty($params['red']) ? $this->formatColor($params['red']) : $this->red,
            'green' => !empty($params['green']) ? $this->formatColor($params['green']) : $this->green,
            'blue' => !empty($params['blue']) ? $this->formatColor($params['blue']) : $this->blue,
        ];

        // channels and their sequence when retrieving statistics
        $vals = ['red', 'green', 'blue'];

        // counts of each color intensity for each channel, initialize to zero
        $counts = [];

        foreach ($vals as $val) {
            $counts[$val] = array_fill(0, 256, 0);
        }

        try {
            // get each unique color in the image and their count
            foreach ($this->imagick->getImageHistogram() as $color) {
                $idx = 0;

                foreach ($color->getColor() as $c) {
                    if (isset($vals[$idx])) {
                        $counts[$vals[$idx++]][$c] += $color->getColorCount();
                    }
                }
            }

            // let's draw a histogram
            $origwidth = 256;
            $width = (int) ($origwidth * $scale);
            $height = (int) floor($width / $ratio);

            // drawing surface for the histogram
            $this->imagick->clear();
            $this->imagick->newImage($width, $height, 'black');

            // get the max value across all arrays
            $max = 0;

            foreach ($vals as $val) {
                $max = max($max, max($counts[$val]));
            }

            // scale each count to the max value and the height of the resulting image
            foreach ($vals as $val) {
                $counts[$val] = array_map(function ($a) use ($max, $height) {
                    return (int) ($height * $a / $max);
                }, $counts[$val]);
            }

            foreach ($vals as $val) {
                // draw a layer for each channel in the image
                $layer = new Imagick();
                $layer->newImage($width, $height, new ImagickPixel('none'));
                $draw = new ImagickDraw();
                $draw->setStrokeColor(new ImagickPixel($colors[$val]));
                $draw->setStrokeAntialias(false);

                foreach ($counts[$val] as $x => $y) {
                    // draw one vertical line for each value in our bucket
                    // if we want to do a scale factor for AA, we repeat it horizontally
                    $x = $x * $scale;

                    for ($i = 0; $i < $scale; $i++) {
                        $draw->line($x + $i, $height, $x + $i, $height - $y);
                    }
                }

                $layer->drawImage($draw);

                // make each layer slightly transparent and composite it into our end image
                $layer->evaluateImage(Imagick::EVALUATE_DIVIDE, 1.1, Imagick::CHANNEL_ALPHA);
                $this->imagick->compositeImage($layer, Imagick::COMPOSITE_BLEND, 0, 0);
                $layer->clear();
            }

            // set background as transparent before finishing, to allow for proper AA in later
            // transformations
            $this->imagick->transparentPaintImage('black', 0, 0, false);
            $this->imagick->setImageFormat('png');
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        } catch (ImagickPixelException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        // Store the new image
        $size = $this->imagick->getImageGeometry();
        $image = $this->image;
        $image->setWidth($size['width'])
              ->setHeight($size['height'])
              ->setHasBeenTransformed(true);
    }
}
