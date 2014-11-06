<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Image\Transformation;

use Imbo\Image\Transformation\Level,
    Imagick;

/**
 * @covers Imbo\Image\Transformation\Level
 * @group unit
 * @group transformations
 */
class LevelTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Level
     */
    private $transformation;

    /**
     * Set up the transformation instance
     */
    public function setUp() {
        $this->transformation = new Level();
    }

    /**
     * Tear down the transformation instance
     */
    public function tearDown() {
        $this->transformation = null;
    }

    public function getLevelParams() {
        return [
            'no params' => [
                [],
                Imagick::CHANNEL_ALL,
            ],
            'green channel, positive amount' => [
                ['channel' => 'g', 'amount' => 50],
                Imagick::CHANNEL_GREEN,
            ],
            'red+green channel, negative amount' => [
                ['channel' => 'rg', 'amount' => -50],
                Imagick::CHANNEL_GREEN | Imagick::CHANNEL_RED,
            ],
            'all channels, capped amount' => [
                ['channel' => 'all', 'amount' => 1000],
                Imagick::CHANNEL_ALL,
            ],
        ];
    }

    /**
     * @dataProvider getLevelParams
     */
    public function testAdjustsCorrectChannels(array $params, $channel) {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('params')->will($this->returnValue($params));
        $event->expects($this->at(1))->method('getArgument')->with('image')->will($this->returnValue($image));

        $quantum = 65535;
        $imagick = $this->getMock('Imagick');
        $imagick->expects($this->once())->method('getQuantumRange')->will($this->returnValue([
            'quantumRangeLong'   => $quantum,
            'quantumRangeString' => (string) $quantum,
        ]));

        $imagick->expects($this->once())->method('levelImage')->with(
            $this->equalTo(0),
            $this->anything(),
            $this->equalTo($quantum),
            $this->equalTo($channel)
        );

        $this->transformation->setImagick($imagick);
        $this->transformation->transform($event);
    }
}