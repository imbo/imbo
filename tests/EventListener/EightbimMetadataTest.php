<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imagick;
use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\DatabaseException;
use Imbo\Exception\RuntimeException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass Imbo\EventListener\EightbimMetadata
 */
class EightbimMetadataTest extends ListenerTests
{
    protected EightbimMetadata $listener;

    public function setUp(): void
    {
        $this->listener = new EightbimMetadata();
        $this->listener->setImagick(new Imagick());
    }

    protected function getListener(): EightbimMetadata
    {
        return $this->listener;
    }

    /**
     * @covers ::setImagick
     * @covers ::populate
     * @covers ::save
     */
    public function testCanExtractMetadata(): void
    {
        $user = 'user';
        $imageIdentifier = 'imageIdentifier';
        $blob = file_get_contents(FIXTURES_DIR . '/jpeg-with-multiple-paths.jpg');

        $image = $this->createConfiguredMock(Image::class, [
            'getImageIdentifier' => $imageIdentifier,
            'getBlob' => $blob,
        ]);

        $request = $this->createConfiguredMock(Request::class, [
            'getUser' => $user,
            'getImage' => $image,
        ]);

        /** @var DatabaseInterface&MockObject */
        $database = $this->createMock(DatabaseInterface::class);
        $database->expects($this->once())->method('updateMetadata')->with($user, $imageIdentifier, [
            'paths' => ['House', 'Panda'],
        ]);

        /** @var EventInterface&MockObject */
        $event = $this->createMock(EventInterface::class);
        $event
            ->expects($this->exactly(2))
            ->method('getRequest')
            ->willReturn($request);
        $event
            ->expects($this->once())
            ->method('getDatabase')
            ->willReturn($database);

        /** @var array{paths:array<string>} */
        $addedPaths = $this->listener->populate($event);
        $this->assertEquals($addedPaths, ['paths' => ['House', 'Panda']]);

        $this->listener->save($event);
    }

    /**
     * @covers ::save
     */
    public function testReturnsEarlyOnMissingProperties(): void
    {
        /** @var EventInterface&MockObject */
        $event = $this->createMock(EventInterface::class);
        $event
            ->expects($this->never())
            ->method('getRequest');

        $this->assertNull(
            $this->listener->save($event),
            'Did not expect method to return anything',
        );
    }

    /**
     * @covers ::save
     */
    public function testDeletesImageWhenStoringMetadataFails(): void
    {
        $user = 'user';
        $imageIdentifier = 'imageIdentifier';
        $blob = file_get_contents(FIXTURES_DIR . '/jpeg-with-multiple-paths.jpg');

        $image = $this->createConfiguredMock(Image::class, [
            'getImageIdentifier' => $imageIdentifier,
            'getBlob' => $blob,
        ]);

        $request = $this->createConfiguredMock(Request::class, [
            'getUser' => $user,
            'getImage' => $image,
        ]);

        /** @var DatabaseInterface&MockObject */
        $database = $this->createMock(DatabaseInterface::class);
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

        $event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $request,
            'getDatabase' => $database,
        ]);

        $this->listener->populate($event);
        $this->expectExceptionObject(new RuntimeException('Could not store 8BIM-metadata', Response::HTTP_INTERNAL_SERVER_ERROR));
        $this->listener->save($event);
    }
}
