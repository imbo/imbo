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
 * Jpeg image formatter
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Response\Formatters
 */
class Jpeg extends ImageFormatter implements ImageFormatterInterface {
    /**
     * {@inheritdoc}
     */
    public function getContentType() {
        return 'image/jpeg';
    }
}
