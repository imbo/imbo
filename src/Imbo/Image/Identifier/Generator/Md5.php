<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image\Identifier\Generator;

use Imbo\Model\Image;

/**
 * Md5 image identifier generator. Please do not use this except in the
 * early transition phase from Imbo 1.x to Imbo 2.x.
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Image\Identifier\Generator
 */
class Md5 implements GeneratorInterface {
    /**
     * {@inheritdoc}
     */
    public function generate(Image $image) {
        return md5($image->getBlob());
    }

    /**
     * {@inheritdoc}
     */
    public function isDeterministic() {
        return true;
    }
}
