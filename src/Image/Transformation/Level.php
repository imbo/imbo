<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException,
    ImagickException,
    Imagick;

/**
 * Level transformation
 *
 * This transformation can be used to adjust the level of RGB/CMYK in an image.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Level extends Transformation {
    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        $channel = isset($params['channel']) ? $params['channel'] : 'all';
        $amount = isset($params['amount']) ? $params['amount'] : 1;

        if ($amount < -100) {
            $amount = -100;
        } else if ($amount > 100) {
            $amount = 100;
        }

        if ($amount < 0) {
            // amounts from -100 to 0 gets translated to 0 to 1
            $gamma = 1 - abs($amount) / 100;
        } else {
            // amount from 0 to 100 gets translated to 1 to 10
            $gamma = floor(($amount / 10.1)) + 1;
        }

        if ($channel === 'all') {
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
                if (strpos($channel, $id) !== false) {
                    $c |= $value;
                }
            }

            $channel = $c;
        }

        try {
            $this->imagick->levelImage(0, (float) $gamma, $this->getQuantumRange(), $channel);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        $this->image->hasBeenTransformed(true);
    }
}
