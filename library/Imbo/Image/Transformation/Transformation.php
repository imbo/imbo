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

use Imagick;

/**
 * Abstract transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
abstract class Transformation {
    /**
     * Imagick instance
     *
     * @var Imagick
     */
    private $imagick;

    /**
     * {@inheritdoc}
     */
    public function getImagick() {
        if ($this->imagick === null) {
            $this->imagick = new Imagick();
            $this->imagick->setOption('png:exclude-chunks', 'all');
        }

        return clone $this->imagick;
    }

    /**
     * {@inheritdoc}
     */
    public function setImagick(Imagick $imagick) {
        $this->imagick = $imagick;

        return $this;
    }

    /**
     * Attempt to format a color-string into a string Imagick can understand
     *
     * @param string $color
     * @return string
     */
    protected function formatColor($color) {
        if (preg_match('/^[A-F0-9]{3,6}$/i', $color)) {
            return '#' . $color;
        }

        return $color;
    }
}
