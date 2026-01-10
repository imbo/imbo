<?php declare(strict_types=1);

namespace Imbo\EventListener;

use Imagick;

/**
 * Imagick aware interface.
 *
 * Any EventListener with this interface will receive a call to its `setImagick()` method.
 */
interface ImagickAware
{
    /**
     * Set the Imagick instance.
     *
     * @param Imagick $imagick Imagick instance
     *
     * @return self
     */
    public function setImagick(Imagick $imagick);
}
