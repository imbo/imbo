<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use Imagick;
use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

/**
 * Level transformation.
 *
 * This transformation can be used to adjust the level of RGB/CMYK in an image.
 */
class Level extends Transformation
{
    public function transform(array $params)
    {
        $channel = isset($params['channel']) ? $params['channel'] : 'all';
        $amount = isset($params['amount']) ? (int) $params['amount'] : 1;

        if ($amount < -100) {
            $amount = -100;
        } elseif ($amount > 100) {
            $amount = 100;
        }

        if ($amount < 0) {
            // amounts from -100 to 0 gets translated to 0 to 1
            $gamma = 1 - abs($amount) / 100;
        } else {
            // amount from 0 to 100 gets translated to 1 to 10
            $gamma = floor($amount / 10.1) + 1;
        }

        if ('all' === $channel) {
            $channel = Imagick::CHANNEL_ALL;
        } else {
            $c = null;
            $channels = [
                'r' => Imagick::CHANNEL_RED,
                'g' => Imagick::CHANNEL_GREEN,
                'b' => Imagick::CHANNEL_BLUE,
                'c' => Imagick::CHANNEL_CYAN,
                'm' => Imagick::CHANNEL_MAGENTA,
                'y' => Imagick::CHANNEL_YELLOW,
                'k' => Imagick::CHANNEL_BLACK,
            ];

            foreach ($channels as $id => $value) {
                if (str_contains($channel, $id)) {
                    $c |= $value;
                }
            }

            $channel = $c;
        }

        try {
            $this->imagick->levelImage(0, (float) $gamma, $this->getQuantumRange(), $channel);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $this->image->setHasBeenTransformed(true);
    }
}
