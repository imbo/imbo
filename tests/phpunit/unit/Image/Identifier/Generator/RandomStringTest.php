<?php
namespace ImboUnitTest\Image\Identifier\Generator;

use Imbo\Image\Identifier\Generator\RandomString as RandomStringGenerator;
use ImagickException;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Image\Identifier\Generator\RandomString
 * @group unit
 */
class RandomStringTest extends TestCase {
    public function testGeneratesUniqueStrings() {
        $stringLength = 15;

        $image = $this->createMock('Imbo\Model\Image');
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
