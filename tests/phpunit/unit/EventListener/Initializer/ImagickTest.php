<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener\Initializer;

use Imbo\EventListener\Initializer\Imagick;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\EventListener\Initializer\Imagick
 * @coversDefaultClass Imbo\EventListener\Initializer\Imagick
 * @group unit
 * @group listeners
 */
class ImagickTest extends TestCase {
    /**
     * @covers ::initialize
     */
    public function testInjectsImagickIntoEventListeners() {
        $imagick = $this->createMock('Imagick');

        $listener = $this->createMock('Imbo\EventListener\Imagick');
        $listener->expects($this->once())
                 ->method('setImagick')
                 ->with($imagick);

        (new Imagick($imagick))->initialize($listener);
    }

    /**
     * @covers ::__construct
     * @covers ::initialize
     */
    public function testCanCreateAnImagickInstanceByItself() {
        $listener = $this->createMock('Imbo\Image\Transformation\Border');
        $listener->expects($this->once())->method('setImagick')->with($this->isInstanceOf('Imagick'));

        (new Imagick())->initialize($listener);
    }
}
