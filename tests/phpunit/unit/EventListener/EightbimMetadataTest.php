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
use Imbo\Exception\DatabaseException;
use Imbo\Exception\RuntimeException;

/**
 * @coversDefaultClass Imbo\EventListener\EightbimMetadata
 * @group unit
 * @group listeners
 */
class EightbimMetadataTest extends ListenerTests {
    /**
     * @var EightbimMetadata
     */
    protected $listener;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->listener = new EightbimMetadata();
        $this->listener->setImagick(new \Imagick());
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * @covers ::populate
     * @covers ::save
     */
    public function testCanExtractMetadata() {
        $user = 'user';
        $imageIdentifier = 'imageIdentifier';
        $blob = file_get_contents(FIXTURES_DIR . '/jpeg-with-multiple-paths.jpg');

        $image = $this->createConfiguredMock('Imbo\Model\Image', [
            'getImageIdentifier' => $imageIdentifier,
            'getBlob' => $blob,
        ]);

        $request = $this->createConfiguredMock('Imbo\Http\Request\Request', [
            'getUser' => $user,
            'getImage' => $image,
        ]);

        $database = $this->createMock('Imbo\Database\DatabaseInterface');
        $database->expects($this->once())->method('updateMetadata')->with($user, $imageIdentifier, [
            'paths' => ['House', 'Panda'],
        ]);

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->exactly(2))->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->once())->method('getDatabase')->will($this->returnValue($database));

        $addedPaths = $this->listener->populate($event);
        $this->assertInternalType('array', $addedPaths);
        $this->assertEquals($addedPaths, ['paths' => ['House', 'Panda']]);

        $this->listener->save($event);
    }

    /**
     * @covers ::save
     */
    public function testReturnsEarlyOnMissingProperties() {
        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->never())->method('getRequest');
        $this->assertNull($this->listener->save($event), 'Did not expect method to return anything');
    }

    /**
     * @covers ::save
     */
    public function testDeletesImageWhenStoringMetadataFails() {
        $user = 'user';
        $imageIdentifier = 'imageIdentifier';
        $blob = file_get_contents(FIXTURES_DIR . '/jpeg-with-multiple-paths.jpg');

        $image = $this->createConfiguredMock('Imbo\Model\Image', [
            'getImageIdentifier' => $imageIdentifier,
            'getBlob' => $blob,
        ]);

        $request = $this->createConfiguredMock('Imbo\Http\Request\Request', [
            'getUser' => $user,
            'getImage' => $image,
        ]);

        $database = $this->createMock('Imbo\Database\DatabaseInterface');
        $database
            ->expects($this->once())
            ->method('updateMetadata')
            ->with($user, $imageIdentifier, [
                'paths' => ['House', 'Panda'],
            ])
            ->willThrowException(new DatabaseException('No can do'));
        $database
            ->expects($this->once())
            ->method('deleteImage')
            ->with($user, $imageIdentifier);

        $event = $this->createConfiguredMock('Imbo\EventManager\Event', [
            'getRequest' => $request,
            'getDatabase' => $database,
        ]);

        $this->listener->populate($event);
        $this->expectExceptionObject(new RuntimeException('Could not store 8BIM-metadata', 500));
        $this->listener->save($event);
    }
}
