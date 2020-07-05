<?php declare(strict_types=1);
namespace Imbo\Image\Identifier\Generator;

use Imbo\Image\Identifier\Generator\Uuid as UuidGenerator;
use Imbo\Model\Image;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Identifier\Generator\Uuid
 */
class UuidTest extends TestCase {
    /**
     * @covers ::generate
     */
    public function testGeneratesUniqueUuidV4() : void {
        $image = $this->createMock(Image::class);
        $generator = new UuidGenerator();
        $generated = [];

        for ($i = 0; $i < 100; $i++) {
            $imageIdentifier = $generator->generate($image);

            // Does it have the right format?
            $this->assertMatchesRegularExpression(
                '/^[a-f0-9]{8}\-[a-f0-9]{4}\-4[a-f0-9]{3}\-[89ab][a-f0-9]{3}\-[a-f0-9]{12}$/',
                $imageIdentifier
            );

            $generated[] = $imageIdentifier;
        }

        $this->assertSame(count($generated), count(array_unique($generated)), 'Expected array to have unique values');
    }
}
