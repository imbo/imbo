<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Image\Identifier\Generator;

use Imbo\Image\Identifier\Generator\RandomString as RandomStringGenerator,
    ImagickException;

/**
 * @covers Imbo\Image\Identifier\Generator\RandomString
 * @group unit
 */
class RandomStringTest extends \PHPUnit_Framework_TestCase {
    public function testGeneratesUniqueStrings() {
        $stringLength = 15;

        $image = $this->getMock('Imbo\Model\Image');
        $generator = new RandomStringGenerator($stringLength);
        $generated = [];

        for ($i = 0; $i < 15; $i++) {
            $imageIdentifier = $generator->generate($image);

            // Does it have the right format?
            $this->assertRegExp(
                '/^[A-Za-z0-9_-]{' . $stringLength . '}$/',
                $imageIdentifier
            );

            // Make sure it doesn't generate any duplicates
            $this->assertFalse(in_array($imageIdentifier, $generated));
            $generated[] = $imageIdentifier;
        }
    }
}
