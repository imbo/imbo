<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\EventListener\ImageVariations\Storage;

use Imbo\EventListener\ImageVariations\Storage\StorageInterface;

/**
 * @group integration
 * @group storage
 */
abstract class StorageTests extends \PHPUnit_Framework_TestCase {
    /**
     * @var StorageInterface
     */
    private $adapter;

    /**
     * Get the adapter we want to test
     *
     * @return StorageInterface
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

    public function testCanStoreAndFetchImageVariations() {
        $key = 'key';
        $id  = 'imageId';
        $width = 200;
        $blob = file_get_contents(FIXTURES_DIR . '/colors.png');

        $this->assertNull($this->adapter->getImageVariation($key, $id, $width));
        $this->assertTrue($this->adapter->storeImageVariation($key, $id, $blob, $width));
        $this->assertSame($blob, $this->adapter->getImageVariation($key, $id, $width));
    }

    public function testCanDeleteOneOrMoreImageVariations() {
        $key = 'key';
        $id  = 'imageId';
        $blob = file_get_contents(FIXTURES_DIR . '/colors.png');

        $this->assertTrue($this->adapter->storeImageVariation($key, $id, $blob, 100));
        $this->assertTrue($this->adapter->storeImageVariation($key, $id, 'blob2', 200));
        $this->assertTrue($this->adapter->storeImageVariation($key, $id, 'blob3', 300));

        $this->assertSame($blob, $this->adapter->getImageVariation($key, $id, 100));
        $this->assertSame('blob2', $this->adapter->getImageVariation($key, $id, 200));
        $this->assertSame('blob3', $this->adapter->getImageVariation($key, $id, 300));

        $this->assertTrue($this->adapter->deleteImageVariations($key, $id, 100));
        $this->assertNull($this->adapter->getImageVariation($key, $id, 100));
        $this->assertSame('blob2', $this->adapter->getImageVariation($key, $id, 200));
        $this->assertSame('blob3', $this->adapter->getImageVariation($key, $id, 300));

        $this->assertTrue($this->adapter->deleteImageVariations($key, $id));
        $this->assertNull($this->adapter->getImageVariation($key, $id, 200));
        $this->assertNull($this->adapter->getImageVariation($key, $id, 300));
    }
}
