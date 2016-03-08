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

use Imbo\Image\Transformation\Level;

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

    /**
     * @covers Imbo\Image\Transformation\Level::getSubscribedEvents
     */
    public function testReturnsEventsForSubscribers() {
        $this->assertSame(['image.transformation.level' => 'transform'], Level::getSubscribedEvents());
    }
}
