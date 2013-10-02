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

use Imbo\Model\Image,
    Imbo\Storage\ImageReader,
    Imbo\Storage\ImageReaderAware;

/**
 * Transformation collection
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Collection extends Transformation implements ImageReaderAware, TransformationInterface {
    /**
     * Image reader instance
     *
     * @var ImageReader
     */
    private $imageReader;

    /**
     * Transformations to apply to the image
     *
     * @var TransformationInterface[]
     */
    private $transformations = array();

    /**
     * Class constructor
     *
     * @param TransformationInterface[] $transformations An array of transformation instances
     */
    public function __construct(array $transformations) {
        $this->transformations = $transformations;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image) {
        foreach ($this->transformations as $transformation) {
            $transformation->applyToImage($image);
        }

        return $this;
    }

    /**
     * Set an instance of an image reader
     *
     * @param ImageReader $reader An image reader instance
     */
    public function setImageReader(ImageReader $reader) {
        foreach ($this->transformations as $transformation) {
            if ($transformation instanceof ImageReaderAware) {
                $transformation->setImageReader($reader);
            }
        }

        $this->imageReader = $reader;
    }

    /**
     * Get an instance of an image reader
     *
     * @return ImageReader An image reader instance
     */
    public function getImageReader() {
        return $this->imageReader;
    }
}
