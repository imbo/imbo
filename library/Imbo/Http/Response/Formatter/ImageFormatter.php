<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http\Response\Formatter;

use Imbo\Model,
    Imbo\Image\Transformation;

/**
 * Gif image formatter
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Response\Formatters
 */
abstract class ImageFormatter implements ImageFormatterInterface {
    /**
     * Mime type => image type map
     *
     * @var array
     */
    private $types = array(
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/png' => 'png',
    );

    /**
     * {@inheritdoc}
     */
    public function format(Model\Image $model) {
        $contentType = $this->getContentType();

        if ($contentType === $model->getMimeType()) {
            return $model->getBlob();
        }

        $model->transform('convert', array('type' => $this->types[$contentType]))
              ->hasBeenTransformed(true);

        return $model->getBlob();
    }
}
