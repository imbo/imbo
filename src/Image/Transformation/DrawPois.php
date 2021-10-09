<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use ImagickDraw;
use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

class DrawPois extends Transformation
{
    /**
     * Color of the border
     */
    private string $color = '#f00';

    /**
     * Size of the border
     */
    private int $borderSize = 2;

    /**
     * Size of the "points" (points of interest without a width/height)
     */
    private int $pointSize = 30;

    public function transform(array $params): void
    {
        $pois = $this->getPoisFromMetadata();

        if (empty($pois) || !is_array($pois)) {
            return;
        }

        $color = !empty($params['color']) ? $this->formatColor($params['color']) : $this->color;
        $borderSize = isset($params['borderSize']) ? (int) $params['borderSize'] : $this->borderSize;
        $pointSize = isset($params['pointSize']) ? (int) $params['pointSize'] : $this->pointSize;

        $imageWidth = $this->image->getWidth();
        $imageHeight = $this->image->getHeight();

        try {
            foreach ($pois as $poi) {
                if (isset($poi['width']) && isset($poi['height']) && isset($poi['x']) && isset($poi['y'])) {
                    $this->drawPoiRectangle($poi, $color, $borderSize - 1, $imageWidth, $imageHeight);
                } elseif (isset($poi['cx']) && isset($poi['cy'])) {
                    $this->drawPoiCircle($poi, $color, $borderSize, $pointSize);
                } else {
                    throw new TransformationException(
                        'Point of interest had neither `width` and `height` nor `cx` and `cy`',
                    );
                }
            }
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $this->image->setHasBeenTransformed(true);
    }

    /**
     * Draw rectangle around a POI
     *
     * @param array{x:int,y:int,width:int,height:int} $poi
     */
    private function drawPoiRectangle(array $poi, string $color, int $borderSize, int $imageWidth, int $imageHeight): void
    {
        $rect = new ImagickDraw();
        $rect->setStrokeColor($color);
        $rect->setFillColor($color);
        $rect->setStrokeAntialias(false);

        $x1 = max(0, $poi['x'] - $borderSize);
        $x2 = min($imageWidth, $poi['x'] + $poi['width'] + $borderSize);

        // Left
        $rect->rectangle(
            $x1,
            $poi['y'],
            $poi['x'],
            $poi['y'] + $poi['height'],
        );

        // Right
        $rect->rectangle(
            $poi['x'] + $poi['width'],
            $poi['y'],
            $x2,
            $poi['y'] + $poi['height'],
        );

        // Top
        $rect->rectangle(
            $x1,
            max(0, $poi['y'] - $borderSize),
            $x2,
            $poi['y'],
        );

        // Bottom
        $rect->rectangle(
            $x1,
            $poi['y'] + $poi['height'],
            $x2,
            min($imageHeight, $poi['y'] + $poi['height'] + $borderSize),
        );

        // Draw the rectangle
        $this->imagick->drawImage($rect);
    }

    /**
     * Draw a circle/dot to mark a POI
     *
     * @param array{cx:float,cy:float} $poi
     */
    private function drawPoiCircle(array $poi, string $color, int $borderSize, int $pointSize): void
    {
        $dot = new ImagickDraw();
        $dot->setStrokeColor($color);
        $dot->setFillColor('transparent');
        $dot->setStrokeAntialias(true);
        $dot->setStrokeWidth($borderSize);

        $dot->circle($poi['cx'], $poi['cy'], $poi['cx'] + $pointSize, $poi['cy'] + $pointSize);

        // Draw the border
        $this->imagick->drawImage($dot);
    }

    /**
     * Fetch POIs from metadata for the image
     *
     * @return array Array with POIs
     */
    private function getPoisFromMetadata(): array
    {
        $metadata = $this->event->getDatabase()->getMetadata(
            $this->image->getUser(),
            $this->image->getImageIdentifier(),
        );

        if (!array_key_exists('poi', $metadata) || !is_array($metadata['poi'])) {
            return [];
        }

        return $metadata['poi'];
    }
}
