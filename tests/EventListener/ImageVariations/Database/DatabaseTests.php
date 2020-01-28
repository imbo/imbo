<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Database;

use PHPUnit\Framework\TestCase;

abstract class DatabaseTests extends TestCase {
    private $adapter;

    /**
     * Get the adapter we want to test
     *
     * @return DatabaseInterface
     */
    abstract protected function getAdapter();

    public function setUp() : void {
        $this->adapter = $this->getAdapter();
    }

    public function getVariationData() : array {
        return [
            'image larger than all variations' => [1000, null],
            'pick the largest variation' => [500, ['width' => 770, 'height' => 564]],
            'pick the next largest variation' => [385, ['width' => 385, 'height' => 282]],
            'pick the smallest variation' => [150, ['width' => 192, 'height' => 140]],
        ];
    }

    /**
     * @dataProvider getVariationData
     * @covers ::storeImageVariationMetadata
     * @covers ::getBestMatch
     */
    public function testCanFetchTheBestMatch(int $imageWidth, ?array $bestMatch) : void {
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

    /**
     * @covers ::storeImageVariationMetadata
     * @covers ::getBestMatch
     * @covers ::deleteImageVariations
     */
    public function testCanDeleteOneOrMoreVariations() : void {
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

        $this->assertSame(null, $this->adapter->getBestMatch('key', 'id', 100));
    }

    /**
     * @covers ::storeImageVariationMetadata
     * @covers ::deleteImageVariations
     * @covers ::getBestMatch
     */
    public function testCanDeleteAllTransformations() : void {
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
        $this->assertSame(null, $this->adapter->getBestMatch('key', 'id', 100));
    }
}
