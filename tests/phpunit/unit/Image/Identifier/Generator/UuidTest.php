<?php
namespace ImboUnitTest\Image\Identifier\Generator;

use Imbo\Image\Identifier\Generator\Uuid as UuidGenerator;
use ImagickException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Identifier\Generator\Uuid
 */
class UuidTest extends TestCase {
    public function testGeneratesUniqueUuidV4() {
        $image = $this->createMock('Imbo\Model\Image');
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
