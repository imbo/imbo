<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Image\InputLoader;

use Imbo\Image\InputLoader\Basic;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\InputLoader\Basic
 */
class BasicTest extends TestCase {
    /**
     * @var Basic
     */
    private $loader;

    /**
     * Set up the loader
     */
    public function setup() {
        $this->loader = new Basic();
    }

    /**
     * @covers ::getSupportedMimeTypes
     */
    public function testReturnsSupportedMimeTypes() {
        $types = $this->loader->getSupportedMimeTypes();

        $this->assertInternalType('array', $types);

        $this->assertContains('image/png', array_keys($types));
        $this->assertContains('image/jpeg', array_keys($types));
        $this->assertContains('image/gif', array_keys($types));
        $this->assertContains('image/tiff', array_keys($types));
    }

    /**
     * @covers ::load
     */
    public function testLoadsImage() {
        $blob = file_get_contents(FIXTURES_DIR . '/1024x256.png');

        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->once())
                ->method('readImageBlob')
                ->with($blob);

        $this->assertNull($this->loader->load($imagick, $blob, 'image/png'));
    }
}
