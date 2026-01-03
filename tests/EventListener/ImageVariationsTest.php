<?php declare(strict_types=1);
namespace Imbo\EventListener;

use DateTime;
use Exception;
use Imbo\EventListener\ImageVariations\Database\DatabaseInterface;
use Imbo\EventListener\ImageVariations\Storage\StorageInterface;
use Imbo\EventManager\EventInterface;
use Imbo\EventManager\EventManager;
use Imbo\Exception\DatabaseException;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\StorageException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Image\Transformation\Convert;
use Imbo\Image\Transformation\Resize;
use Imbo\Image\Transformation\Transformation;
use Imbo\Image\TransformationManager;
use Imbo\Model\Image;
use Imbo\Storage\StorageInterface as MainStorageInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[CoversClass(ImageVariations::class)]
class ImageVariationsTest extends ListenerTests
{
    private ImageVariations $listener;
    private DatabaseInterface&MockObject $db;
    private StorageInterface&MockObject $storage;
    private EventInterface&MockObject $event;
    private Request&MockObject $request;
    private Response&MockObject $response;
    private ResponseHeaderBag&MockObject $responseHeaders;
    private InputBag $query;
    private Image&MockObject $imageModel;
    private MainStorageInterface&MockObject $imageStorage;
    private EventManager&MockObject $eventManager;
    private TransformationManager&MockObject $transformationManager;
    private string $user = 'user';
    private string $imageIdentifier = 'imgid';

    protected function getListener(): ImageVariations
    {
        return new ImageVariations([
            'database' => [
                'adapter' => $this->db,
            ],
            'storage' => [
                'adapter' => $this->storage,
            ],
        ]);
    }

    public function tearDown(): void
    {
        restore_error_handler();
    }

    public function setUp(): void
    {
        set_error_handler(
            function (int $errno, string $errstr) {
                if (0 !== error_reporting()) {
                    throw new ErrorException($errstr, $errno);
                }
            },
        );

        $this->db         = $this->createMock(DatabaseInterface::class);
        $this->storage    = $this->createMock(StorageInterface::class);
        $this->query      = new InputBag();
        $this->imageModel = $this->createConfiguredMock(Image::class, [
            'getImageIdentifier' => $this->imageIdentifier,
        ]);
        $this->imageModel
            ->expects($this->any())
            ->method('setWidth')
            ->willReturnSelf();
        $this->imageModel
            ->method('setHeight')
            ->willReturnSelf();
        $this->imageModel
            ->method('setMimeType')
            ->willReturnSelf();
        $this->imageModel
            ->method('setExtension')
            ->willReturnSelf();
        $this->eventManager = $this->createMock(EventManager::class);
        $this->imageStorage = $this->createMock(MainStorageInterface::class);

        $this->transformationManager = $this->createMock(TransformationManager::class);
        $this->request               = $this->createConfiguredMock(Request::class, [
            'getUser'            => $this->user,
            'getImageIdentifier' => $this->imageIdentifier,
            'getImage'           => $this->imageModel,
        ]);
        $this->request->query = $this->query;

        $this->responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $this->response        = $this->createConfiguredMock(Response::class, [
            'getModel' => $this->imageModel,
        ]);
        $this->response->headers = $this->responseHeaders;

        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest'               => $this->request,
            'getResponse'              => $this->response,
            'getManager'               => $this->eventManager,
            'getStorage'               => $this->imageStorage,
            'getTransformationManager' => $this->transformationManager,
        ]);

        $this->listener = $this->getListener();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsOnInvalidScaleFactor(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Scale factor must be below 1', Response::HTTP_SERVICE_UNAVAILABLE));
        new ImageVariations([
            'database' => [
                'adapter' => $this->db,
            ],
            'storage' => [
                'adapter' => $this->storage,
            ],
            'scaleFactor' => 1.5,
        ]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsOnMissingDatabaseAdapter(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Missing database adapter configuration for the image variations event listener',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        new ImageVariations([
            'storage' => [
                'adapter' => $this->storage,
            ],
        ]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsOnInvalidDatabaseFromCallable(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid database adapter for the image variations event listener',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        new ImageVariations([
            'database' => [
                'adapter' => function () {
                    return null;
                },
            ],
            'storage' => [
                'adapter' => $this->storage,
            ],
        ]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsOnInvalidDatabaseFromString(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid database adapter for the image variations event listener',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        new ImageVariations([
            'database' => [
                'adapter' => stdClass::class,
            ],
            'storage' => [
                'adapter' => $this->storage,
            ],
        ]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsOnMissingStorageAdapter(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Missing storage adapter configuration for the image variations event listener',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        new ImageVariations([
            'database' => [
                'adapter' => $this->db,
            ],
        ]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsOnInvalidStorageFromCallable(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid storage adapter for the image variations event listener',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        new ImageVariations([
            'storage'  => [
                'adapter' => function () {
                    return null;
                },
            ],
            'database' => [
                'adapter' => $this->db,
            ],
        ]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsOnInvalidStorageFromString(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid storage adapter for the image variations event listener',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        new ImageVariations([
            'storage'  => [
                'adapter' => stdClass::class,
            ],
            'database' => [
                'adapter' => $this->db,
            ],
        ]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testFallsBackIfNoTransformationsAreApplied(): void
    {
        $this->request
            ->method('getTransformations')
            ->willReturn([]);

        $this->eventManager
            ->expects($this->never())
            ->method('trigger');

        $this->listener->chooseVariation($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testFallsBackIfNoRelevantTransformationsApplied(): void
    {
        $width  = 1024;
        $height = 768;
        $transformations = [
            [
                'name'   => 'desaturate',
                'params' => [],
            ],
        ];

        $this->imageModel
            ->method('getWidth')
            ->willReturn($width);

        $this->imageModel
            ->method('getHeight')
            ->willReturn($height);

        $this->request
            ->method('getTransformations')
            ->willReturn($transformations);

        $this->transformationManager
            ->expects($this->once())
            ->method('getMinimumImageInputSize')
            ->willReturn(false);

        $this->eventManager
            ->expects($this->never())
            ->method('trigger');

        $this->listener->chooseVariation($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testFallsBackIfSizeIsLargerThanOriginal(): void
    {
        $width  = 1024;
        $height = 768;
        $transformations = [
            [
                'name'   => 'maxSize',
                'params' => ['width' => 2048],
            ],
        ];

        $this->imageModel
            ->method('getWidth')
            ->willReturn($width);

        $this->imageModel
            ->method('getHeight')
            ->willReturn($height);

        $this->request
            ->method('getTransformations')
            ->willReturn($transformations);

        $this->transformationManager
            ->expects($this->once())
            ->method('getMinimumImageInputSize')
            ->willReturn([
                'index' => 0,
                'width' => 2048,
            ]);

        $this->eventManager
            ->expects($this->never())
            ->method('trigger');

        $this->listener->chooseVariation($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testFallsBackIfDatabaseDoesNotReturnAnyVariation(): void
    {
        $width  = 1024;
        $height = 768;
        $transformations = [
            [
                'name'   => 'maxSize',
                'params' => ['width' => 512],
            ],
        ];

        $this->eventManager
            ->expects($this->never())
            ->method('trigger');

        $this->imageModel
            ->method('getWidth')
            ->willReturn($width);

        $this->imageModel
            ->method('getHeight')
            ->willReturn($height);

        $this->request
            ->method('getTransformations')
            ->willReturn($transformations);

        $this->transformationManager
            ->expects($this->once())
            ->method('getMinimumImageInputSize')
            ->willReturn([
                'index' => 0,
                'width' => 512,
            ]);

        $this->db
            ->expects($this->once())
            ->method('getBestMatch')
            ->with(
                $this->user,
                $this->imageIdentifier,
                512,
            )
            ->willReturn(null);

        $this->listener->chooseVariation($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testTriggersWarningIfVariationFoundInDbButNotStorage(): void
    {
        $width               = 1024;
        $height              = 768;
        $transformationWidth = 512;
        $variationWidth      = 800;
        $transformations     = [
            [
                'name'   => 'desaturate',
                'params' => [],
            ],
            [
                'name'   => 'maxSize',
                'params' => ['width' => $transformationWidth],
            ],
        ];

        $this->imageModel
            ->method('getWidth')
            ->willReturn($width);

        $this->imageModel
            ->method('getHeight')
            ->willReturn($height);

        $this->request
            ->method('getTransformations')
            ->willReturn($transformations);

        $this->db
            ->method('getBestMatch')
            ->with(
                $this->user,
                $this->imageIdentifier,
                $transformationWidth,
            )
            ->willReturn([
                'width' => $variationWidth,
                'height' => 600,
            ]);

        $this->transformationManager
            ->method('getMinimumImageInputSize')
            ->willReturn([
                'index' => 1,
                'width' => $transformationWidth,
            ]);

        $this->eventManager
            ->method('trigger')
            ->with(
                'image.transformations.adjust',
                [
                    'transformationIndex' => 1,
                    'ratio' => $width / $variationWidth,
                ],
            );

        $this->storage
            ->method('getImageVariation')
            ->with(
                $this->user,
                $this->imageIdentifier,
                $variationWidth,
            )
            ->willReturn(null);

        $this->imageStorage
            ->expects($this->never())
            ->method('getLastModified');

        // Running this twice, once with error suppression (to flag "return" for code coverage),
        // once for triggering the warning
        $level = error_reporting(0);
        $this->listener->chooseVariation($this->event);
        error_reporting($level);

        $this->expectExceptionObject(
            new ErrorException('Image variation storage is not in sync with the image variation database', E_USER_WARNING),
        );

        $this->listener->chooseVariation($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testUpdatesResponseAndImageModelOnSuccess(): void
    {
        $width               = 1024;
        $height              = 768;
        $transformationWidth = 512;
        $variationWidth      = 800;
        $variationHeight     = 600;
        $variationBlob       = 'blob';
        $lastModified        = new DateTime('now');
        $transformations     = [
            [
                'name'   => 'desaturate',
                'params' => [],
            ],
            [
                'name'   => 'maxSize',
                'params' => ['width' => $transformationWidth],
            ],
        ];

        $this->imageModel
            ->method('getWidth')
            ->willReturn($width);

        $this->imageModel
            ->method('getHeight')
            ->willReturn($height);

        $this->request
            ->method('getTransformations')
            ->willReturn($transformations);

        $this->db
            ->method('getBestMatch')
            ->with(
                $this->user,
                $this->imageIdentifier,
                $transformationWidth,
            )
            ->willReturn([
                'width'  => $variationWidth,
                'height' => $variationHeight,
            ]);

        $manager = $this->eventManager;
        $manager
            ->method('trigger')
            ->willReturnCallback(
                static function (string $transformation, array $params = []) use ($manager, $width, $variationWidth): EventManager&MockObject {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $transformation, $params]) {
                        [
                            0,
                            'image.transformations.adjust',
                            [
                                'transformationIndex' => 1,
                                'ratio'               => $width / $variationWidth,
                            ],
                        ],
                        [1, 'image.loaded', []] => $manager,
                    };
                },
            );

        $this->storage
            ->expects($this->once())
            ->method('getImageVariation')
            ->with(
                $this->user,
                $this->imageIdentifier,
                $variationWidth,
            )
            ->willReturn($variationBlob);

        $this->imageStorage
            ->expects($this->once())
            ->method('getLastModified')
            ->with(
                $this->user,
                $this->imageIdentifier,
            )
            ->willReturn($lastModified);

        $this->response
            ->expects($this->once())
            ->method('setLastModified')
            ->with($lastModified);

        $this->imageModel
            ->expects($this->once())
            ->method('setBlob')
            ->with($variationBlob)
            ->willReturnSelf();

        $this->imageModel
            ->expects($this->once())
            ->method('setWidth')
            ->with($variationWidth)
            ->willReturnSelf();

        $this->imageModel
            ->expects($this->once())
            ->method('setHeight')
            ->with($variationHeight)
            ->willReturnSelf();

        $this->responseHeaders
            ->expects($this->once())
            ->method('set')
            ->with(
                'X-Imbo-ImageVariation',
                sprintf('%dx%d', $variationWidth, $variationHeight),
            );

        $this->event
            ->expects($this->once())
            ->method('stopPropagation');

        $this->transformationManager
            ->method('getMinimumImageInputSize')
            ->willReturn([
                'index' => 1,
                'width' => $transformationWidth,
            ]);

        $this->listener->chooseVariation($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testTriggersWarningOnFailedDeleteFromDatabase(): void
    {
        $this->db
            ->expects($this->once())
            ->method('deleteImageVariations')
            ->with(
                $this->user,
                $this->imageIdentifier,
            )
            ->willThrowException(new DatabaseException());

        $this->expectExceptionObject(
            new ErrorException('Could not delete image variation metadata for user (imgid)', E_USER_WARNING),
        );

        $this->listener->deleteVariations($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testTriggersWarningOnFailedDeleteFromStorage(): void
    {
        $this->storage
            ->expects($this->once())
            ->method('deleteImageVariations')
            ->with(
                $this->user,
                $this->imageIdentifier,
            )
            ->willThrowException(new StorageException());

        $this->expectExceptionObject(
            new ErrorException('Could not delete image variations from storage for user (imgid)', E_USER_WARNING),
        );

        $this->listener->deleteVariations($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testDoesNotTriggerWarningsOnSuccessfulVariationsDelete(): void
    {
        $this->db
            ->expects($this->once())
            ->method('deleteImageVariations')
            ->with(
                $this->user,
                $this->imageIdentifier,
            );

        $this->storage
            ->expects($this->once())
            ->method('deleteImageVariations')
            ->with(
                $this->user,
                $this->imageIdentifier,
            );

        $this->assertNull($this->listener->deleteVariations($this->event));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testGenerateVariationsCallsStoreImageVariationForEveryWidth(): void
    {
        $listener = new ImageVariations([
            'database' => [
                'adapter' => $this->db,
            ],
            'storage' => [
                'adapter' => $this->storage,
            ],

            /**
             * With autoScale on, it should filter out the first and last two widths,
             * but as we have turned autoScale off, it should only remove the last width,
             * which is larger than our original.
             */
            'widths'    => [25, 100, 400, 800, 1024, 1700, 3000],
            'autoScale' => false,
        ]);

        $this->imageModel
            ->method('getWidth')
            ->willReturn(2048);

        $this->imageModel
            ->method('getHeight')
            ->willReturn(1536);

        $this->imageModel
            ->method('getBlob')
            ->willReturn('some blob');

        $this->imageModel
            ->method('setWidth')
            ->willReturnSelf();

        $this->imageModel
            ->method('setHeight')
            ->willReturnSelf();

        $this->storage
            ->method('storeImageVariation')
            ->willReturnCallback(
                static function (string $user, string $imageIdentifier, string $contents, int $width): bool {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $user, $imageIdentifier, $contents, $width]) {
                        [0, 'user', 'imgid', 'some blob', 25],
                        [1, 'user', 'imgid', 'some blob', 100],
                        [2, 'user', 'imgid', 'some blob', 400],
                        [3, 'user', 'imgid', 'some blob', 800],
                        [4, 'user', 'imgid', 'some blob', 1024],
                        [5, 'user', 'imgid', 'some blob', 1700] => true,
                    };
                },
            );

        $transformation = $this->createMock(Resize::class);
        $transformation
            ->expects($this->exactly(6))
            ->method('setImage')
            ->with($this->isInstanceOf(Image::class))
            ->willReturnSelf();

        $transformation
            ->expects($this->exactly(6))
            ->method('transform')
            ->with($this->callback(
                static function (array $params): bool {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $params['width']]) {
                        [0, 25],
                        [1, 100],
                        [2, 400],
                        [3, 800],
                        [4, 1024],
                        [5, 1700] => true,
                    };
                },
            ));

        $this->transformationManager
            ->expects($this->exactly(6))
            ->method('getTransformation')
            ->with('resize')
            ->willReturn($transformation);

        $this->db
            ->expects($this->exactly(6))
            ->method('storeImageVariationMetadata')
            ->with($this->user, $this->imageIdentifier, $this->greaterThan(0), $this->greaterThan(0));

        $this->eventManager
            ->expects($this->exactly(12))
            ->method('trigger');

        $listener->generateVariations($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testGenerateVariationsWithLosslessParamTriggersPngConversion(): void
    {
        $listener = new ImageVariations([
            'database'  => ['adapter' => $this->db],
            'storage'   => ['adapter' => $this->storage],
            'widths'    => [500],
            'autoScale' => false,
            'lossless'  => true,
        ]);

        $convertTransformation = $this->createMock(Convert::class);
        $convertTransformation
            ->expects($this->once())
            ->method('setImage')
            ->with($this->isInstanceOf(Image::class))
            ->willReturnSelf();

        $convertTransformation
            ->expects($this->once())
            ->method('transform')
            ->with(['type' => 'png']);

        $resizeTransformation = $this->createMock(Resize::class);
        $resizeTransformation
            ->expects($this->once())
            ->method('setImage')
            ->with($this->isInstanceOf(Image::class))
            ->willReturnSelf();

        $resizeTransformation
            ->expects($this->once())
            ->method('transform')
            ->with(['width' => 500]);

        $this->transformationManager
            ->expects($this->exactly(2))
            ->method('getTransformation')
            ->willReturnCallback(
                static function (string $transformation) use ($convertTransformation, $resizeTransformation) {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $transformation]) {
                        [0, 'convert'] => $convertTransformation,
                        [1, 'resize'] => $resizeTransformation,
                    };
                },
            );

        $this->imageModel
            ->method('getWidth')
            ->willReturn(2048);

        $this->imageModel
            ->method('getHeight')
            ->willReturn(1536);

        $this->imageModel
            ->method('getBlob')
            ->willReturn('image data');

        $listener->generateVariations($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testGenerateVariationsAutoScalesRespectingMaxMinWidth(): void
    {
        $listener = new ImageVariations([
            'database'    => ['adapter' => $this->db],
            'storage'     => ['adapter' => $this->storage],
            'minDiff'     => 100,
            'minWidth'    => 320,
            'maxWidth'    => 1300,
            'scaleFactor' => .65,
        ]);

        $resize = $this->createMock(Resize::class);
        $resize
            ->expects($this->exactly(3))
            ->method('setImage')
            ->willReturnSelf();

        $this->transformationManager
            ->expects($this->exactly(3))
            ->method('getTransformation')
            ->willReturn($resize);

        $this->imageModel
            ->method('getWidth')
            ->willReturn(2048);

        $this->imageModel
            ->method('getHeight')
            ->willReturn(1536);

        $this->imageModel
            ->method('getBlob')
            ->willReturn('image data');

        $this->storage
            ->method('storeImageVariation')
            ->willReturnCallback(
                static function (string $user, string $imageIdentifier, string $contents, int $width): bool {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $user, $imageIdentifier, $contents, $width]) {
                        [0, 'user', 'imgid', 'image data', 865],
                        [1, 'user', 'imgid', 'image data', 562],
                        [2, 'user', 'imgid', 'image data', 365] => true,
                    };
                },
            );

        $listener->generateVariations($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testGenerateVariationsIncludesSpecifiedWidths(): void
    {
        $listener = new ImageVariations([
            'database'    => ['adapter' => $this->db],
            'storage'     => ['adapter' => $this->storage],
            'widths'      => [1337],
            'scaleFactor' => .2,
        ]);

        $resize = $this->createMock(Resize::class);
        $resize
            ->method('setImage')
            ->willReturnSelf();

        $this->transformationManager
            ->expects($this->exactly(2))
            ->method('getTransformation')
            ->willReturn($resize);

        $this->imageModel
            ->method('getWidth')
            ->willReturn(2048);

        $this->imageModel
            ->method('getHeight')
            ->willReturn(1536);

        $this->imageModel
            ->method('getBlob')
            ->willReturn('image data');

        $this->storage
            ->method('storeImageVariation')
            ->willReturnCallback(
                static function (string $user, string $imageIdentifier, string $contents, int $width): bool {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $user, $imageIdentifier, $contents, $width]) {
                        [0, 'user', 'imgid', 'image data', 1337],
                        [1, 'user', 'imgid', 'image data', 410] => true,
                    };
                },
            );

        $listener->generateVariations($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testGenerateVariationsTriggersWarningOnTransformationException(): void
    {
        $this->imageModel
            ->method('getWidth')
            ->willReturn(1024);

        $this->imageModel
            ->method('getHeight')
            ->willReturn(768);

        $this->imageModel
            ->method('getBlob')
            ->willReturn('image data');

        $transformation = $this->createMock(Transformation::class);
        $transformation
            ->expects($this->once())
            ->method('setImage')
            ->willReturnSelf();
        $transformation
            ->expects($this->once())
            ->method('transform')
            ->willThrowException(new TransformationException());


        $this->transformationManager
            ->expects($this->once())
            ->method('getTransformation')
            ->with('resize')
            ->willReturn($transformation);

        $this->expectExceptionObject(
            new ErrorException('Could not generate image variation for user (imgid), width: 512', E_USER_WARNING),
        );

        $this->listener->generateVariations($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testGenerateVariationsTriggersWarningOnStorageException(): void
    {
        $this->imageModel
            ->method('getWidth')
            ->willReturn(1024);

        $this->imageModel
            ->method('getHeight')
            ->willReturn(768);

        $this->imageModel
            ->method('getBlob')
            ->willReturn('image data');

        $this->storage
            ->expects($this->once())
            ->method('storeImageVariation')
            ->willThrowException(new StorageException());

        $transformation = $this->createConfiguredMock(Transformation::class, [
            'transform' => null,
        ]);
        $transformation
            ->expects($this->once())
            ->method('setImage')
            ->willReturnSelf();

        $this->transformationManager
            ->expects($this->once())
            ->method('getTransformation')
            ->with('resize')
            ->willReturn($transformation);

        $this->expectExceptionObject(
            new ErrorException('Could not store image variation for user (imgid), width: 512', E_USER_WARNING),
        );

        $this->listener->generateVariations($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testGenerateVariationsTriggersWarningOnDatabaseException(): void
    {
        $this->imageModel
            ->method('getWidth')
            ->willReturn(1024);

        $this->imageModel
            ->method('getHeight')
            ->willReturn(768);

        $this->imageModel
            ->method('getBlob')
            ->willReturn('image data');

        $this->db
            ->expects($this->once())
            ->method('storeImageVariationMetadata')
            ->willThrowException(new DatabaseException());

        $transformation = $this->createConfiguredMock(Transformation::class, [
            'transform' => null,
        ]);
        $transformation
            ->expects($this->once())
            ->method('setImage')
            ->willReturnSelf();

        $this->transformationManager
            ->expects($this->once())
            ->method('getTransformation')
            ->with('resize')
            ->willReturn($transformation);

        $this->expectExceptionObject(
            new ErrorException('Could not store image variation metadata for user (imgid), width: 512', E_USER_WARNING),
        );

        $this->listener->generateVariations($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testTriggersDeletionOfImageVariationsWhenUnableToStoreMetadata(): void
    {
        $listener = new ImageVariations([
            'database'  => ['adapter' => $this->db],
            'storage'   => ['adapter' => $this->storage],
            'widths'    => [1000],
            'autoScale' => false,
        ]);

        $this->imageModel
            ->method('getWidth')
            ->willReturn(1024);

        $this->imageModel
            ->method('getHeight')
            ->willReturn(768);

        $this->imageModel
            ->method('getBlob')
            ->willReturn('image data');

        $this->db
            ->expects($this->once())
            ->method('storeImageVariationMetadata')
            ->with($this->user, )
            ->willThrowException(new DatabaseException());

        $transformation = $this->createConfiguredMock(Transformation::class, [
            'transform' => null,
        ]);
        $transformation
            ->expects($this->once())
            ->method('setImage')
            ->willReturnSelf();

        $this->transformationManager
            ->expects($this->once())
            ->method('getTransformation')
            ->with('resize')
            ->willReturn($transformation);

        $this->storage
            ->expects($this->once())
            ->method('deleteImageVariations')
            ->with($this->user, $this->imageIdentifier, 1000);

        // Need to suppress the warning, otherwise PHPUnit will stop executing the code
        $level = error_reporting(0);
        $listener->generateVariations($this->event);
        error_reporting($level);
    }
}

class ErrorException extends Exception
{
}
