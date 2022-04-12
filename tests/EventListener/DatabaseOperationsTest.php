<?php declare(strict_types=1);
namespace Imbo\EventListener;

use DateTime;
use Imbo\Auth\AccessControl\Adapter\AdapterInterface;
use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use Imbo\Model\Images;
use Imbo\Model\Metadata;
use Imbo\Model\Stats;
use Imbo\Model\User;
use Imbo\Resource\Images\Query;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @coversDefaultClass Imbo\EventListener\DatabaseOperations
 */
class DatabaseOperationsTest extends ListenerTests
{
    private $listener;
    private $event;
    private $request;
    private $response;
    private $database;
    private $user = 'user';
    private $imageIdentifier = 'id';
    private $image;
    private $accessControl;

    public function setUp(): void
    {
        $this->response = $this->createMock(Response::class);
        $this->database = $this->createMock(DatabaseInterface::class);
        $this->accessControl = $this->createMock(AdapterInterface::class);
        $this->image = $this->createMock(Image::class);
        $this->request = $this->createConfiguredMock(Request::class, [
            'getUser' => $this->user,
            'getUsers' => [$this->user],
            'getImageIdentifier' => $this->imageIdentifier,
        ]);
        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getResponse' => $this->response,
            'getRequest' => $this->request,
            'getDatabase' => $this->database,
            'getAccessControl' => $this->accessControl,
        ]);

        $this->listener = new DatabaseOperations();
    }

    protected function getListener(): DatabaseOperations
    {
        return $this->listener;
    }

    /**
     * @covers ::insertImage
     */
    public function testCanInsertImage(): void
    {
        $this->image
            ->expects($this->once())
            ->method('getImageIdentifier')
            ->willReturn($this->imageIdentifier);

        $this->request
            ->method('getImage')
            ->willReturn($this->image);

        $this->database
            ->expects($this->once())
            ->method('insertImage')
            ->with($this->user, $this->imageIdentifier, $this->image);

        $this->listener->insertImage($this->event);
    }

    /**
     * @covers ::deleteImage
     */
    public function testCanDeleteImage(): void
    {
        $this->database
            ->expects($this->once())
            ->method('deleteImage')
            ->with($this->user, $this->imageIdentifier);

        $this->listener->deleteImage($this->event);
    }

    /**
     * @covers ::loadImage
     */
    public function testCanLoadImage(): void
    {
        $this->response
            ->expects($this->any())
            ->method('getModel')
            ->willReturn($this->image);

        $this->database
            ->expects($this->once())
            ->method('load')
            ->with($this->user, $this->imageIdentifier, $this->image);

        $this->listener->loadImage($this->event);
    }

    /**
     * @covers ::deleteMetadata
     */
    public function testCanDeleteMetadata(): void
    {
        $this->database
            ->expects($this->once())
            ->method('deleteMetadata')
            ->with($this->user, $this->imageIdentifier);

        $this->database
            ->expects($this->once())
            ->method('setLastModifiedNow')
            ->with($this->user, $this->imageIdentifier);

        $this->listener->deleteMetadata($this->event);
    }

    /**
     * @covers ::updateMetadata
     */
    public function testCanUpdateMetadata(): void
    {
        $this->event
            ->expects($this->once())
            ->method('getArgument')
            ->with('metadata')
            ->willReturn(['key' => 'value']);

        $this->database
            ->expects($this->once())
            ->method('updateMetadata')
            ->with($this->user, $this->imageIdentifier, ['key' => 'value']);

        $this->database
            ->expects($this->once())
            ->method('setLastModifiedNow')
            ->with($this->user, $this->imageIdentifier);

        $this->listener->updateMetadata($this->event);
    }

    /**
     * @covers ::loadMetadata
     */
    public function testCanLoadMetadata(): void
    {
        $date = new DateTime();
        $this->database
            ->expects($this->once())
            ->method('getMetadata')
            ->with($this->user, $this->imageIdentifier)
            ->willReturn(['key' => 'value']);

        $this->database
            ->expects($this->once())
            ->method('getLastModified')
            ->with([$this->user], $this->imageIdentifier)
            ->willReturn($date);

        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(Metadata::class))
            ->willReturnSelf();

        $this->response
            ->expects($this->once())
            ->method('setLastModified')
            ->with($date);

        $this->listener->loadMetadata($this->event);
    }

    /**
     * @covers ::loadImages
     */
    public function testCanLoadImages(): void
    {
        $images = [
            [
                'added' => new DateTime(),
                'updated' => new DateTime(),
                'size' => 123,
                'width' => 50,
                'height' => 50,
                'imageIdentifier' => 'identifier1',
                'checksum' => 'checksum1',
                'originalChecksum' => 'checksum1',
                'mime' => 'image/png',
                'extension' => 'png',
                'user' => $this->user,
                'metadata' => [],
            ],
            [
                'added' => new DateTime(),
                'updated' => new DateTime(),
                'size' => 456,
                'width' => 60,
                'height' => 60,
                'imageIdentifier' => 'identifier2',
                'checksum' => 'checksum2',
                'originalChecksum' => 'checksum2',
                'mime' => 'image/png',
                'extension' => 'png',
                'user' => $this->user,
                'metadata' => [],
            ],
            [
                'added' => new DateTime(),
                'updated' => new DateTime(),
                'size' => 789,
                'width' => 70,
                'height' => 70,
                'imageIdentifier' => 'identifier3',
                'checksum' => 'checksum3',
                'originalChecksum' => 'checksum3',
                'mime' => 'image/png',
                'extension' => 'png',
                'user' => $this->user,
                'metadata' => [],
            ],
        ];

        $date = new DateTime();

        $query = $this->createMock(ParameterBag::class);
        $query
            ->method('has')
            ->withConsecutive(
                ['page'],
                ['limit'],
                ['metadata'],
                ['from'],
                ['to'],
                ['sort'],
                ['ids'],
                ['checksums'],
                ['originalChecksums'],
            )
            ->willReturn(true);
        $query
            ->method('get')
            ->withConsecutive(
                ['page'],
                ['limit'],
                ['from'],
                ['to'],
                ['sort'],
                ['ids'],
                ['checksums'],
                ['originalChecksums'],
            )
            ->willReturnOnConsecutiveCalls(
                1,
                5,
                1355156488,
                1355176488,
                ['size:desc'],
                ['identifier1', 'identifier2', 'identifier3'],
                ['checksum1', 'checksum2', 'checksum3'],
                ['checksum1', 'checksum2', 'checksum3'],
            );

        $this->request->query = $query;

        $imagesQuery = $this->createMock(Query::class);
        $this->listener->setImagesQuery($imagesQuery);

        $this->database
            ->expects($this->once())
            ->method('getImages')
            ->with([$this->user], $imagesQuery)
            ->willReturn($images);

        $this->database
            ->expects($this->once())
            ->method('getLastModified')
            ->with([$this->user])
            ->willReturn($date);

        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(Images::class))
            ->willReturnSelf();

        $this->response
            ->expects($this->once())
            ->method('setLastModified')
            ->with($date);

        $this->listener->loadImages($this->event);
    }


    /**
     * @covers ::loadUser
     */
    public function testCanLoadUser(): void
    {
        $date = new DateTime();
        $this->database
            ->expects($this->once())
            ->method('getNumImages')
            ->with($this->user)
            ->willReturn(123);

        $this->database
            ->expects($this->once())
            ->method('getLastModified')
            ->with([$this->user])
            ->willReturn($date);

        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(User::class))
            ->willReturnSelf();

        $this->response
            ->expects($this->once())
            ->method('setLastModified')
            ->with($date);

        $this->listener->loadUser($this->event);
    }

    /**
     * @covers ::loadStats
     */
    public function testCanLoadStats(): void
    {
        $this->database
            ->method('getNumImages')
            ->willReturnOnConsecutiveCalls(1, 2);

        $this->database
            ->method('getNumBytes')
            ->willReturn(1);

        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(Stats::class))
            ->willReturnSelf();

        $this->listener->loadStats($this->event);
    }

    /**
     * @covers ::getImagesQuery
     * @covers ::setImagesQuery
     */
    public function testCanCreateItsOwnImagesQuery(): void
    {
        $this->assertInstanceOf(Query::class, $this->listener->getImagesQuery());

        $query = $this->createMock(Query::class);
        $this->assertSame($this->listener, $this->listener->setImagesQuery($query));
        $this->assertSame($query, $this->listener->getImagesQuery());
    }
}
