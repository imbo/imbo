<?php
namespace Imbo\Image;

/**
 * Region extractor interface - transformations that implement this interface
 * can let Imbo know that the transformation will return a region of the input
 * image, given a set of parameters.
 */
interface RegionExtractor {
    /**
     * Get the region of the image that is extracted when applying the transformation
     * with the parameters provided.
     *
     * @param array $params Transformation parameters
     * @param array $imageSize Size of image
     * @return array Array containing `width`, `height`, `x` and `y`
     */
    public function getExtractedRegion(array $params, array $imageSize);
}
