<?php declare(strict_types=1);

namespace Imbo\Image\Identifier\Generator;

use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function count;

#[CoversClass(RandomString::class)]
class RandomStringTest extends TestCase
{
    public function testGeneratesUniqueStrings(): void
    {
        $stringLength = 15;

        $image = $this->createStub(Image::class);
        $generator = new RandomString($stringLength);
        $generated = [];

        for ($i = 0; $i < 100; ++$i) {
            $imageIdentifier = $generator->generate($image);

            $this->assertMatchesRegularExpression(
                '/^[A-Za-z0-9_-]{'.$stringLength.'}$/',
                $imageIdentifier,
            );

            $generated[] = $imageIdentifier;
        }

        $this->assertCount(
            count($generated),
            array_unique($generated),
            'Expected array to have unique values',
        );
    }
}
