<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException,
    ImagickException;

/**
 * Strip properties and comments from an image
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Strip extends Transformation {
    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        try {
            $this->imagick->stripImage();
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        // In newer versions of Imagick, it seems we need to clear and re-read
        // the data to properly clear the properties
        $data = $this->imagick->getImageBlob();
        $this->imagick->clear();
        $this->imagick->readImageBlob($data);

        $this->image->hasBeenTransformed(true);
    }
}
