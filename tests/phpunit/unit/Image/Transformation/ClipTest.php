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

use Imbo\Exception\InvalidArgumentException,
    Imbo\Image\Transformation\Clip,
    Imbo\Model\Image;

/**
 * @covers Clip
 * @group unit
 * @group transformations
 */
class ClipTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Clip
     */
    private $transformation;

    /**
     * @var Image
     */
    private $image;

    /**
     * Imagick instance for testing
     *
     * @var \Imagick
     */
    private $imagick;

    /**
     * Set up the transformation instance
     */
    public function setUp() {
        $this->transformation = new Clip();

        $user = 'user';
        $imageIdentifier = 'imageIdentifier';
        $blob = file_get_contents(FIXTURES_DIR . '/jpeg-with-multiple-paths.jpg');

        $this->image = $this->createMock('Imbo\Model\Image');
        $this->image->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $this->image->expects($this->any())->method('getUser')->will($this->returnValue($user));

        $database = $this->createMock('Imbo\Database\DatabaseInterface');
        $database->expects($this->any())->method('getMetadata')->with($user, $imageIdentifier)->will($this->returnValue([
            'paths' => ['House', 'Panda'],
        ]));

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getDatabase')->will($this->returnValue($database));

        $this->transformation->setEvent($event);
        $this->transformation->setImage($this->image);

        $this->imagick = new \Imagick();
        $this->imagick->readImageBlob($blob);
        $this->transformation->setImagick($this->imagick);
    }

    /**
     * Tear down the transformation instance
     */
    public function tearDown() {
        $this->transformation = null;
    }

    /**
     * @covers Clip::transform
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp #clipping path .* not found#
     */
    public function testExceptionIfMissingNamedPath() {
        $this->transformation->transform(['name' => 'foo']);
    }

    /**
     * @covers Clip::transform
     */
    public function testNoExceptionIfMissingNamedPathButIgnoreSet() {
        $this->transformation->transform(['name' => 'foo', 'ignoreUnknownPath' => '']);
    }

    /**
     * @covers Clip::transform
     */
    public function testTransformationHappensWithMatchingPath() {
        $this->image->expects($this->atLeastOnce())->method('hasBeenTransformed')->with(true);
        $this->transformation->transform(['name' => 'Panda']);
    }

    /**
     * @covers Clip::transform
     */
    public function testTransformationHappensWithoutExplicitPath() {
        $this->image->expects($this->atLeastOnce())->method('hasBeenTransformed')->with(true);
        $this->transformation->transform([]);
    }

    /**
     * @covers Clip::transform
     */
    public function testTransformationDoesntHappenWhenNoPathIsPresent() {
        $this->imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.jpg'));
        $this->image->expects($this->never())->method('hasBeenTransformed');

        $this->transformation->transform([]);
    }

}
