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
    Imbo\Image\Transformation\TransformationInterface;

/**
 * Gif image formatter
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Response\Formatters
 */
class Gif implements ImageFormatterInterface {
    /**
     * Convert transformation
     *
     * @var TransformationInterface
     */
    private $transformation;

    /**
     * Class constructor
     *
     * @param TransformationInterface $transformation A convert transformation
     */
    public function __construct(TransformationInterface $transformation) {
        $this->transformation = $transformation;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType() {
        return 'image/gif';
    }

    /**
     * {@inheritdoc}
     */
    public function formatImage(Model\Image $model) {

    }
}
