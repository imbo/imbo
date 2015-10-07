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

/**
 * @covers Imbo\EventListener\Initializer\Imagick
 * @group unit
 * @group listeners
 */
class ImagickTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imagick
     */
    private $initializer;

    private $imagick;

    /**
     * Set up the initializer
     */
    public function setUp() {
        $this->imagick = $this->getMock('Imagick');
        $this->initializer = new Imagick($this->imagick);
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->imagick = null;
        $this->initializer = null;
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getListeners() {
        return [
            'image transformation' => [$this->getMock('Imbo\Image\Transformation\Border'), true],
            'regular transformation' => [$this->getMock('Imbo\EventListener\ListenerInterface'), false],
        ];
    }

    /**
     * @covers Imbo\EventListener\Initializer\Imagick::initialize
     * @dataProvider getListeners
     */
    public function testInjectsImagickIntoEventListeners($listener, $injects) {
        if ($injects) {
            $listener->expects($this->once())->method('setImagick')->with($this->imagick);
        }

        $this->initializer->initialize($listener);
    }

    /**
     * @covers Imbo\EventListener\Initializer\Imagick::__construct
     * @covers Imbo\EventListener\Initializer\Imagick::initialize
     */
    public function testCanCreateAnImagickInstanceByItself() {
        $listener = $this->getMock('Imbo\Image\Transformation\Border');
        $listener->expects($this->once())->method('setImagick')->with($this->isInstanceOf('Imagick'));

        $initializer = new Imagick();
        $initializer->initialize($listener);
    }
}
