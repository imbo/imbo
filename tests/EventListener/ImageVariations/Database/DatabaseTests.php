<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Database;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTests extends TestCase
{
    private DatabaseInterface $adapter;

    abstract protected function getAdapter(): DatabaseInterface;

    protected function setUp(): void
    {
        $this->adapter = $this->getAdapter();
    }

    /**
     * @param array<string,array{imageWidth:int,bestMatch:?array{width:int,height:int}}> $bestMatch
     */
    #[DataProvider('getVariationData')]
    public function testCanFetchTheBestMatch(int $imageWidth, ?array $bestMatch): void
    {
        $variations = [
            [
                'width' => 770,
                'height' => 564,
            ],
            [
                'width' => 385,
                'height' => 282,
            ],
            [
                'width' => 192,
                'height' => 140,
            ],
        ];

        foreach ($variations as $variation) {
            $this->assertTrue($this->adapter->storeImageVariationMetadata('key', 'id', $variation['width'], $variation['height']));
        }

        $this->assertSame($bestMatch, $this->adapter->getBestMatch('key', 'id', $imageWidth));
    }

    public function testCanDeleteOneOrMoreVariations(): void
    {
        $variations = [
            [
                'width' => 770,
                'height' => 564,
            ],
            [
                'width' => 385,
                'height' => 282,
            ],
            [
                'width' => 192,
                'height' => 140,
            ],
        ];

        foreach ($variations as $variation) {
            $this->assertTrue($this->adapter->storeImageVariationMetadata('key', 'id', $variation['width'], $variation['height']));
        }

        $this->assertSame($variations[2], $this->adapter->getBestMatch('key', 'id', 100));
        $this->adapter->deleteImageVariations('key', 'id', 192);

        $this->assertSame($variations[1], $this->adapter->getBestMatch('key', 'id', 100));
        $this->adapter->deleteImageVariations('key', 'id', 385);

        $this->assertSame($variations[0], $this->adapter->getBestMatch('key', 'id', 100));
        $this->adapter->deleteImageVariations('key', 'id', 770);

        $this->assertNull($this->adapter->getBestMatch('key', 'id', 100));
    }

    public function testCanDeleteAllTransformations(): void
    {
        $variations = [
            [
                'width' => 770,
                'height' => 564,
            ],
            [
                'width' => 385,
                'height' => 282,
            ],
            [
                'width' => 192,
                'height' => 140,
            ],
        ];

        foreach ($variations as $variation) {
            $this->assertTrue($this->adapter->storeImageVariationMetadata('key', 'id', $variation['width'], $variation['height']));
        }

        $this->assertTrue($this->adapter->deleteImageVariations('key', 'id'));
        $this->assertNull($this->adapter->getBestMatch('key', 'id', 100));
    }

    /**
     * @return array<string,array{imageWidth:int,bestMatch:?array{width:int,height:int}}>
     */
    public static function getVariationData(): array
    {
        return [
            'image larger than all variations' => [
                'imageWidth' => 1000,
                'bestMatch' => null,
            ],
            'pick the largest variation' => [
                'imageWidth' => 500,
                'bestMatch' => [
                    'width' => 770,
                    'height' => 564,
                ],
            ],
            'pick the next largest variation' => [
                'imageWidth' => 385,
                'bestMatch' => [
                    'width' => 385,
                    'height' => 282,
                ],
            ],
            'pick the smallest variation' => [
                'imageWidth' => 150,
                'bestMatch' => [
                    'width' => 192,
                    'height' => 140,
                ],
            ],
        ];
    }
}
