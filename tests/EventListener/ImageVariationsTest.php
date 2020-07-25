<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventListener\ImageVariations;
use Imbo\Exception\DatabaseException;
use Imbo\Exception\StorageException;
use Imbo\Exception\TransformationException;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Image\TransformationManager;
use Imbo\EventListener\ImageVariations\Database\DatabaseInterface;
use Imbo\EventListener\ImageVariations\Storage\StorageInterface;
use Imbo\EventManager\EventManager;
use Imbo\Model\Image;
use Imbo\Storage\StorageInterface as MainStorageInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Image\Transformation\Transformation;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use DateTime;
use Imbo\Image\Transformation\Convert;
use Imbo\Image\Transformation\Resize;
use stdClass;

/**
 * @coversDefaultClass Imbo\EventListener\ImageVariations
 */
class ImageVariationsTest extends ListenerTests {
    private ImageVariations $listener;
    private DatabaseInterface $db;
    private StorageInterface $storage;
    private EventInterface $event;
    private Request $request;
    private Response $response;
    private ResponseHeaderBag $responseHeaders;
    private ParameterBag $query;
    private Image $imageModel;
    private MainStorageInterface $imageStorage;
    private EventManager $eventManager;
    private TransformationManager $transformationManager;
    private string $user = 'user';
    private string $imageIdentifier = 'imgid';

    protected function getListener() : ImageVariations {
        return new ImageVariations([
            'database' => [
                'adapter' => $this->db,
            ],
            'storage' => [
                'adapter' => $this->storage,
            ],
        ]);
    }

    public function setUp() : void {
        $this->db         = $this->createMock(DatabaseInterface::class);
        $this->storage    = $this->createMock(StorageInterface::class);
        $this->query      = $this->createMock(ParameterBag::class);
        $this->imageModel = $this->createConfiguredMock(Image::class, [
            'getImageIdentifier' => $this->imageIdentifier,
            //'setWidth'           => $this->returnSelf(),
            //'setHeight'          => $this->returnSelf(),
            //'setMimeType'        => $this->returnSelf(),
            //'setExtension'       => $this->returnSelf(),
        ]);
        $this->imageModel
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
        $this->config       = [];

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

    /**
     * @covers ::__construct
     */
    public function testThrowsOnInvalidScaleFactor() : void {
        $this->expectExceptionObject(new InvalidArgumentException('Scale factor must be below 1', 503));
        new ImageVariations([
            'database' => [
                'adapter' => $this->db,
            ],
            'storage' => [
                'adapter' => $this->storage,
            ],
            'scaleFactor' => 1.5
        ]);
    }

    /**
     * @covers ::__construct
     * @covers ::configureDatabase
     */
    public function testThrowsOnMissingDatabaseAdapter() : void {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Missing database adapter configuration for the image variations event listener',
            500
        ));
        new ImageVariations([
            'storage' => [
                'adapter' => $this->storage,
            ],
        ]);
    }

    /**
     * @covers ::__construct
     * @covers ::configureDatabase
     */
    public function testThrowsOnInvalidDatabaseFromCallable() : void {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid database adapter for the image variations event listener',
            500
        ));
        new ImageVariations([
            'database' => [
                'adapter' => function() { return null; },
            ],
            'storage' => [
                'adapter' => $this->storage,
            ],
        ]);
    }

    /**
     * @covers ::__construct
     * @covers ::configureDatabase
     */
    public function testThrowsOnInvalidDatabaseFromString() : void {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid database adapter for the image variations event listener',
            500
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

    /**
     * @covers ::__construct
     * @covers ::configureStorage
     */
    public function testThrowsOnMissingStorageAdapter() : void {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Missing storage adapter configuration for the image variations event listener',
            500
        ));
        new ImageVariations([
            'database' => [
                'adapter' => $this->db,
            ],
        ]);
    }

    /**
     * @covers ::__construct
     * @covers ::configureStorage
     */
    public function testThrowsOnInvalidStorageFromCallable() : void {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid storage adapter for the image variations event listener',
            500
        ));
        new ImageVariations([
            'storage'  => [
                'adapter' => function() { return null; },
            ],
            'database' => [
                'adapter' => $this->db,
            ],
        ]);
    }

    /**
     * @covers ::__construct
     * @covers ::configureStorage
     */
    public function testThrowsOnInvalidStorageFromString() : void {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid storage adapter for the image variations event listener',
            500
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

    /**
     * @covers ::chooseVariation
     */
    public function testFallsBackIfNoTransformationsAreApplied() : void {
        $this->request
            ->method('getTransformations')
            ->willReturn([]);

        $this->eventManager
            ->expects($this->never())
            ->method('trigger');

        $this->listener->chooseVariation($this->event);
    }

    /**
     * @covers ::chooseVariation
     */
    public function testFallsBackIfNoRelevantTransformationsApplied() : void {
        $width  = 1024;
        $height = 768;
        $transformations = [
            [
                'name'   => 'desaturate',
                'params' => []
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

    /**
     * @covers ::chooseVariation
     */
    public function testFallsBackIfSizeIsLargerThanOriginal() : void {
        $width  = 1024;
        $height = 768;
        $transformations = [
            [
                'name'   => 'maxSize',
                'params' => ['width' => 2048]
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

    /**
     * @covers ::chooseVariation
     */
    public function testFallsBackIfDatabaseDoesNotReturnAnyVariation() : void {
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
                512
            )
            ->willReturn(null);

        $this->listener->chooseVariation($this->event);
    }

    /**
     * @covers ::chooseVariation
     */
    public function testTriggersWarningIfVariationFoundInDbButNotStorage() : void {
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
                'params' => ['width' => $transformationWidth]
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
                $transformationWidth
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
                'image.transformations.adjust', [
                    'transformationIndex' => 1,
                    'ratio' => $width / $variationWidth,
                ]
            );

        $this->storage
            ->method('getImageVariation')
            ->with(
                $this->user,
                $this->imageIdentifier,
                $variationWidth
            )
            ->willReturn(null);

        $this->imageStorage
            ->expects($this->never())
            ->method('getLastModified');

        // Running this twice, once with error suppression (to flag "return" for code coverage),
        // once for triggering the warning
        @$this->listener->chooseVariation($this->event);

        $this->expectWarning();
        $this->expectWarningMessage('Image variation storage is not in sync with the image variation database');
        $this->listener->chooseVariation($this->event);
    }

    /**
     * @covers ::chooseVariation
     */
    public function testUpdatesResponseAndImageModelOnSuccess() : void {
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
                $transformationWidth
            )
            ->willReturn([
                'width'  => $variationWidth,
                'height' => $variationHeight,
            ]);

        $this->eventManager
            ->method('trigger')
            ->withConsecutive(
                [
                    'image.transformations.adjust',
                    [
                        'transformationIndex' => 1,
                        'ratio'               => $width / $variationWidth,
                    ],
                ],
                [
                    'image.loaded'
                ]
            );

        $this->storage
            ->expects($this->once())
            ->method('getImageVariation')
            ->with(
                $this->user,
                $this->imageIdentifier,
                $variationWidth
            )
            ->willReturn($variationBlob);

        $this->imageStorage
            ->expects($this->once())
            ->method('getLastModified')
            ->with(
                $this->user,
                $this->imageIdentifier
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
                sprintf('%dx%d', $variationWidth, $variationHeight)
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

    /**
     * @covers ::deleteVariations
     */
    public function testTriggersWarningOnFailedDeleteFromDatabase() : void {
        $this->db
            ->expects($this->once())
            ->method('deleteImageVariations')
            ->with(
                $this->user,
                $this->imageIdentifier
            )
            ->willThrowException(new DatabaseException());

        $this->expectWarning();
        $this->expectWarningMessage(
            'Could not delete image variation metadata for user (imgid)'
        );

        $this->listener->deleteVariations($this->event);
    }

    /**
     * @covers ::deleteVariations
     */
    public function testTriggersWarningOnFailedDeleteFromStorage() : void {
        $this->storage
            ->expects($this->once())
            ->method('deleteImageVariations')
            ->with(
                $this->user,
                $this->imageIdentifier
            )
            ->willThrowException(new StorageException());

        $this->expectWarning();
        $this->expectWarningMessage(
            'Could not delete image variations from storage for user (imgid)'
        );

        $this->listener->deleteVariations($this->event);
    }

    /**
     * @covers ::deleteVariations
     */
    public function testDoesNotTriggerWarningsOnSuccessfulVariationsDelete() : void {
        $this->db
            ->expects($this->once())
            ->method('deleteImageVariations')
            ->with(
                $this->user,
                $this->imageIdentifier
            );

        $this->storage
            ->expects($this->once())
            ->method('deleteImageVariations')
            ->with(
                $this->user,
                $this->imageIdentifier
            );

        $this->assertNull($this->listener->deleteVariations($this->event));
    }

    /**
     * @covers ::generateVariations
     */
    public function testGenerateVariationsCallsStoreImageVariationForEveryWidth() : void {
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
            ->withConsecutive(
                [$this->user, $this->imageIdentifier, 'some blob', 25],
                [$this->user, $this->imageIdentifier, 'some blob', 100],
                [$this->user, $this->imageIdentifier, 'some blob', 400],
                [$this->user, $this->imageIdentifier, 'some blob', 800],
                [$this->user, $this->imageIdentifier, 'some blob', 1024],
                [$this->user, $this->imageIdentifier, 'some blob', 1700],
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
            ->withConsecutive(
                [['width' => 25]],
                [['width' => 100]],
                [['width' => 400]],
                [['width' => 800]],
                [['width' => 1024]],
                [['width' => 1700]],
            );

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

    /**
     * @covers ::generateVariations
     */
    public function testGenerateVariationsWithLosslessParamTriggersPngConversion() : void {
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
            ->withConsecutive(
                ['convert'],
                ['resize'],
            )
            ->willReturnOnConsecutiveCalls(
                $convertTransformation,
                $resizeTransformation,
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

    /**
     * @covers ::generateVariations
     */
    public function testGenerateVariationsAutoScalesRespectingMaxMinWidth() : void {
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
            ->expects($this->exactly(3))
            ->method('storeImageVariation')
            ->withConsecutive(
                [$this->user, $this->imageIdentifier, $this->anything(), 865],
                [$this->user, $this->imageIdentifier, $this->anything(), 562],
                [$this->user, $this->imageIdentifier, $this->anything(), 365]
            );

        $listener->generateVariations($this->event);
    }

    /**
     * @covers ::generateVariations
     */
    public function testGenerateVariationsIncludesSpecifiedWidths() : void {
        $listener = new ImageVariations([
            'database'    => ['adapter' => $this->db],
            'storage'     => ['adapter' => $this->storage],
            'widths'      => [1337],
            'scaleFactor' => .2,
        ]);

        $this->transformationManager
            ->expects($this->exactly(2))
            ->method('getTransformation')
            ->willReturn($this->createConfiguredMock(Resize::class, [
                'setImage' => $this->returnSelf(),
            ]));

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
            ->expects($this->exactly(2))
            ->method('storeImageVariation')
            ->withConsecutive(
                [$this->user, $this->imageIdentifier, $this->anything(), 1337],
                [$this->user, $this->imageIdentifier, $this->anything(), 410]
            );

        $listener->generateVariations($this->event);
    }

    /**
     * @covers ::generateVariations
     */
    public function testGenerateVariationsTriggersWarningOnTransformationException() : void {
        $this->imageModel
            ->method('getWidth')
            ->willReturn(1024);

        $this->imageModel
            ->method('getHeight')
            ->willReturn(768);

        $this->imageModel
            ->method('getBlob')
            ->willReturn('image data');

        $this->transformationManager
            ->expects($this->once())
            ->method('getTransformation')
            ->with('resize')
            ->willReturn($this->createConfiguredMock(Transformation::class, [
                'setImage'  => $this->returnSelf(),
                'transform' => $this->throwException(new TransformationException())
            ]));

        $this->expectWarning();
        $this->expectWarningMessage(
            'Could not generate image variation for user (imgid), width: 512'
        );

        $this->listener->generateVariations($this->event);
    }

    /**
     * @covers ::generateVariations
     */
    public function testGenerateVariationsTriggersWarningOnStorageException() : void {
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

        $this->transformationManager
            ->expects($this->once())
            ->method('getTransformation')
            ->with('resize')
            ->willReturn($this->createConfiguredMock(Transformation::class, [
                'setImage'  => $this->returnSelf(),
                'transform' => null,
            ]));

        $this->expectWarning();
        $this->expectWarningMessage(
            'Could not store image variation for user (imgid), width: 512'
        );

        $this->listener->generateVariations($this->event);
    }

    /**
     * @covers ::generateVariations
     */
    public function testGenerateVariationsTriggersWarningOnDatabaseException() : void {
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

        $this->transformationManager
            ->expects($this->once())
            ->method('getTransformation')
            ->with('resize')
            ->willReturn($this->createConfiguredMock(Transformation::class, [
                'setImage'  => $this->returnSelf(),
                'transform' => null,
            ]));

        $this->expectWarning();
        $this->expectWarningMessage(
            'Could not store image variation metadata for user (imgid), width: 512'
        );

        $this->listener->generateVariations($this->event);
    }

    /**
     * @covers ::generateVariations
     */
    public function testTriggersDeletionOfImageVariationsWhenUnableToStoreMetadata() : void {
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

        $this->transformationManager
            ->expects($this->once())
            ->method('getTransformation')
            ->with('resize')
            ->willReturn($this->createConfiguredMock(Transformation::class, [
                'setImage'  => $this->returnSelf(),
                'transform' => null,
            ]));

        $this->storage
            ->expects($this->once())
            ->method('deleteImageVariations')
            ->with($this->user, $this->imageIdentifier, 1000);

        // Need to suppress the warning, otherwise PHPUnit will stop executing the code
        @$listener->generateVariations($this->event);
    }
}
