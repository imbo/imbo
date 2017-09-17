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

use Imbo\Image\InputLoader\Text;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass Imbo\Image\InputLoader\Text
 */
class TextTest extends PHPUnit_Framework_TestCase {
    /**
     * @var Text
     */
    private $loader;

    /**
     * Set up the loader
     */
    public function setup() {
        $this->loader = new Text();
    }

    /**
     * @covers ::getSupportedMimeTypes
     */
    public function testReturnsSupportedMimeTypes() {
        $types = $this->loader->getSupportedMimeTypes();

        $this->assertInternalType('array', $types);

        $this->assertContains('text/plain', array_keys($types));
    }

    /**
     * @covers ::load
     */
    public function testLoadsImage() {
        $blob = 'some text';

        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->once())
                ->method('readImageBlob')
                ->with($this->isType('string'));

        $this->assertNull($this->loader->load($imagick, $blob, 'plain/text'));
    }
}
