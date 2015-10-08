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

use Imbo\Image\Identifier\Generator\Uuid as UuidGenerator,
    ImagickException;

/**
 * @covers Imbo\Image\Identifier\Generator\Uuid
 * @group unit
 */
class UuidTest extends \PHPUnit_Framework_TestCase {
    public function testGeneratesUniqueUuidV4() {
        $image = $this->getMock('Imbo\Model\Image');
        $generator = new UuidGenerator();
        $generated = [];

        for ($i = 0; $i < 15; $i++) {
            $imageIdentifier = $generator->generate($image);

            // Does it have the right format?
            $this->assertRegExp(
                '/^[a-f0-9]{8}\-[a-f0-9]{4}\-4[a-f0-9]{3}\-[89ab][a-f0-9]{3}\-[a-f0-9]{12}$/',
                $imageIdentifier
            );

            // Make sure it doesn't generate any duplicates
            $this->assertFalse(in_array($imageIdentifier, $generated));
            $generated[] = $imageIdentifier;
        }
    }
}
