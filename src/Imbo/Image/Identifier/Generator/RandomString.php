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
 * Random string image identifier generator
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Core\Image\Identifier\Generator
 */
class RandomString implements GeneratorInterface {
    /**
     * Class constructor
     *
     * @param integer $length The length of the randomly generated string
     */
    public function __construct($length = 12) {
        $this->stringLength = $length;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(Image $image) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890_-';
        $charsLen = strlen($chars);
        $key = '';

        for ($i = 0; $i < $this->stringLength; $i++) {
            $key .= $chars[mt_rand(0, $charsLen - 1)];
        }

        return $key;
    }

    /**
     * {@inheritdoc}
     */
    public function isDeterministic() {
        return false;
    }
}
