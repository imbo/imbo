<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener;

use Imbo\EventListener\EightbimMetadata;

/**
 * @covers Imbo\EventListener\ExifMetadata
 * @group unit
 * @group listeners
 */
class EightbimMetadataTest extends ListenerTests {
    /**
     * Set up the listener
     */
    public function setUp() {
        $this->listener = new EightbimMetadata();
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->listener = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    public function testCanExtractMetadata() {
        $user = 'user';
        $imageIdentifier = 'imageIdentifier';
        $blob = file_get_contents(FIXTURES_DIR . '/jpeg-with-multiple-paths.jpg');

        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $image->expects($this->once())->method('getBlob')->will($this->returnValue($blob));

        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getUser')->will($this->returnValue($user));
        $request->expects($this->any())->method('getImage')->will($this->returnValue($image));

        $database = $this->createMock('Imbo\Database\DatabaseInterface');
        $database->expects($this->once())->method('updateMetadata')->with($user, $imageIdentifier, [
            'paths' => ['House', 'Panda'],
        ]);

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->exactly(2))->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->once())->method('getDatabase')->will($this->returnValue($database));

        $listener = new EightbimMetadata();
        $listener->populate($event);
        $listener->save($event);
    }
}
