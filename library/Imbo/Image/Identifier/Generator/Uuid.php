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

use Imbo\Model\Image,
    Ramsey\Uuid\Uuid as UuidFactory;

/**
 * UUID image identifier generator
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Image\Identifier\Generator
 */
class Uuid implements GeneratorInterface {
    /**
     * {@inheritdoc}
     */
    public function generate(Image $image) {
        return (string) UuidFactory::uuid4();
    }

    /**
     * {@inheritdoc}
     */
    public function isDeterministic() {
        return false;
    }
}
