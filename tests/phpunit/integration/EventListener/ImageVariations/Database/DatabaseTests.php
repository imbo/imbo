<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\EventListener\ImageVariations\Database;

use Imbo\EventListener\ImageVariations\Database\DatabaseInterface;

/**
 * @group integration
 * @group database
 */
abstract class DatabaseTests extends \PHPUnit_Framework_TestCase {
    /**
     * @var DatabaseInterface
     */
    private $adapter;

    /**
     * Get the adapter we want to test
     *
     * @return DatabaseInterface
     */
    abstract protected function getAdapter();

    /**
     * Set up
     */
    public function setUp() {
        $this->adapter = $this->getAdapter();
    }

    /**
     * Tear down
     */
    public function tearDown() {
        $this->adapter = null;
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getVariationData() {
        return [
            'image larger than all variations' => [1000, null],
            'pick the largest variation' => [500, ['width' => 770, 'height' => 564]],
            'pick the next largest variation' => [385, ['width' => 385, 'height' => 282]],
            'pick the smallest variation' => [150, ['width' => 192, 'height' => 140]],
        ];
    }

    /**
     * @dataProvider getVariationData
     */
    public function testCanFetchTheBestMatch($imageWidth, $bestMatch) {
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

    public function testCanDeleteOneOrMoreVariations() {
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

    public function testCanDeleteAllTransformations() {
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
