<?php declare(strict_types=1);
namespace Imbo\Image\Identifier\Generator;

use Imbo\Image\Identifier\Generator\RandomString as RandomStringGenerator;
use Imbo\Model\Image;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Identifier\Generator\RandomString
 */
class RandomStringTest extends TestCase {
    public function testGeneratesUniqueStrings() : void {
        $stringLength = 15;

        $image = $this->createMock(Image::class);
        $generator = new RandomStringGenerator($stringLength);
        $generated = [];

        for ($i = 0; $i < 100; $i++) {
            $imageIdentifier = $generator->generate($image);

            // Does it have the right format?
            $this->assertMatchesRegularExpression(
                '/^[A-Za-z0-9_-]{' . $stringLength . '}$/',
                $imageIdentifier
            );

            $generated[] = $imageIdentifier;
        }

        $this->assertSame(count($generated), count(array_unique($generated)), 'Expected array to have unique values');
    }
}
