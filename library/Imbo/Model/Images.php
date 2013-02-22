<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Model;

/**
 * Images model
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
class Images implements ModelInterface {
    /**
     * An array of Image models
     *
     * @var Image[]
     */
    private $images = array();

    /**
     * Set the array of images
     *
     * @param Image[] $images An array of Image models
     * @return Images
     */
    public function setImages(array $images) {
        $this->images = $images;

        return $this;
    }

    /**
     * Get the images
     *
     * @return Image[]
     */
    public function getImages() {
        return $this->images;
    }
}
