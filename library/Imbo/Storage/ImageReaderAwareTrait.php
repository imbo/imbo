<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Storage;

/**
 * Image reader aware trait
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Storage
 */
trait ImageReaderAwareTrait {
    /**
     * Image reader instance
     * 
     * @var ImageReader
     */
    private $imageReader;

    /**
     * {@inheritdoc}
     */
    public function setImageReader(ImageReader $reader) {
        $this->imageReader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function getImageReader() {
        return $this->imageReader;
    }
}