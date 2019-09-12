<?php
namespace Imbo\EventListener;

/**
 * Imagick aware interface
 *
 * Any EventListener with this interface will receive a call to its `setImagick()` method.
 *
 * @package Event\Listeners
 */
interface ImagickAware {
    /**
     * Set the Imagick instance
     *
     * @param \Imagick $imagick Imagick instance
     * @return self
     */
    function setImagick(\Imagick $imagick);
}
