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

use Imbo\Image\Image,
    Imbo\Exception\TransformationException,
    Imagick;

/**
 * Image transformation interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
interface TransformationInterface {
    /**
     * Get the name of the transformation
     *
     * The name should be the one used in the query to trigger the transformation. Usually this is
     * the last part of the class name, in lower case.
     *
     * @return string
     */
    function getName();

    /**
     * Set the name of the transformation
     *
     * This method is mostly used by presets (chains)
     *
     * @param string $name The name of the transformation
     * @return TransformationInterface
     */
    function setName($name);

    /**
     * Get the imagick instance
     *
     * @return Imagick
     */
    function getImagick();

    /**
     * Set the imagick instance
     *
     * @param Imagick $imagick
     * @return TransformationInterface
     */
    function setImagick(Imagick $imagick);

    /**
     * Apply a transformation to an image object
     *
     * @param Image Image instance
     * @throws TransformationException
     */
    function applyToImage(Image $image);
}
