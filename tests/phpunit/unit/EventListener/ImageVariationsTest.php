<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventListener\ImageVariations;
use Imbo\Exception\DatabaseException;
use Imbo\Exception\StorageException;
use Imbo\Exception\TransformationException;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Image\TransformationManager;
use Imbo\EventListener\Initializer\Imagick as ImagickInitializer;
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
use Imagick;

/**
 * @coversDefaultClass Imbo\EventListener\ImageVariations
 */
class ImageVariationsTest extends ListenerTests {
    private $listener;
    private $db;
    private $config;
    private $storage;
    private $event;
    private $request;
    private $response;
    private $responseHeaders;
    private $query;
    private $imagick;
    private $imageModel;
    private $imageStorage;
    private $eventManager;
    private $transformationManager;
    private $user = 'user';
    private $imageIdentifier = 'imgid';
    private $transformation;

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
        $this->db = $this->createMock(DatabaseInterface::class);
        $this->storage = $this->createMock(StorageInterface::class);

        $this->query = $this->createMock(ParameterBag::class);
        $this->imageModel = $this->createConfiguredMock(Image::class, [
            'getImageIdentifier' => $this->imageIdentifier,
            'setWidth' => $this->returnSelf(),
            'setHeight' => $this->returnSelf(),
            'setMimeType' => $this->returnSelf(),
            'setExtension' => $this->returnSelf(),
        ]);
        $this->eventManager = $this->createMock(EventManager::class);
        $this->imageStorage = $this->createMock(MainStorageInterface::class);
        $this->imagick = $this->createMock(Imagick::class);

        $this->config = require __DIR__ . '/../../../../config/config.default.php';

        $this->transformationManager = new TransformationManager();
        $this->transformationManager->addTransformations($this->config['transformations']);
        $this->transformationManager->addInitializer(new ImagickInitializer($this->imagick));
        $this->request = $this->createConfiguredMock(Request::class, [
            'getUser' => $this->user,
            'getImageIdentifier' => $this->imageIdentifier,
            'getImage' => $this->imageModel,
        ]);
        $this->request->query = $this->query;

        $this->responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $this->response = $this->createConfiguredMock(Response::class, [
            'getModel' => $this->imageModel,
        ]);
        $this->response->headers = $this->responseHeaders;

        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getManager' => $this->eventManager,
            'getStorage' => $this->imageStorage,
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
            'storage' => [ 'adapter' => $this->storage ],
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
            'database' => [ 'adapter' => function() { return null; } ],
            'storage'  => [ 'adapter' => $this->storage ],
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
            'database' => [ 'adapter' => 'DateTime' ],
            'storage'  => [ 'adapter' => $this->storage ],
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
            'database' => [ 'adapter' => $this->db ],
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
            'storage'  => [ 'adapter' => function() { return null; } ],
            'database' => [ 'adapter' => $this->db ],
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
            'storage'  => [ 'adapter' => 'DateTime' ],
            'database' => [ 'adapter' => $this->db ],
        ]);
    }

    /**
     * @covers ::chooseVariation
     */
    public function testFallsBackIfNoTransformationsAreApplied() : void {
        $this->request
            ->expects($this->any())
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
        $transformations = [[
            'name'   => 'desaturate',
            'params' => []
        ]];

        $this->imageModel
            ->expects($this->any())
            ->method('getWidth')
            ->willReturn($width);
        $this->imageModel
            ->expects($this->any())
            ->method('getHeight')
            ->willReturn($height);

        $this->request
            ->expects($this->any())
            ->method('getTransformations')
            ->willReturn($transformations);

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
        $transformations = [[
            'name'   => 'maxSize',
            'params' => ['width' => $width * 2]
        ]];

        $this->imageModel
            ->expects($this->any())
            ->method('getWidth')
            ->willReturn($width);

        $this->imageModel
            ->expects($this->any())
            ->method('getHeight')
            ->willReturn($height);

        $this->request
            ->expects($this->any())
            ->method('getTransformations')
            ->willReturn($transformations);

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
        $transformations = [[
            'name'   => 'maxSize',
            'params' => ['width' => 512]
        ]];

        $this->eventManager
            ->expects($this->never())
            ->method('trigger');

        $this->imageModel
            ->expects($this->any())
            ->method('getWidth')
            ->willReturn($width);

        $this->imageModel
            ->expects($this->any())
            ->method('getHeight')
            ->willReturn($height);

        $this->request
            ->expects($this->any())
            ->method('getTransformations')
            ->willReturn($transformations);

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
        $width  = 1024;
        $height = 768;
        $transformationWidth = 512;
        $variationWidth = 800;
        $transformations = [[
            'name'   => 'desaturate',
            'params' => [],
        ],[
            'name'   => 'maxSize',
            'params' => ['width' => $transformationWidth]
        ]];

        $this->imageModel
            ->expects($this->atLeastOnce())
            ->method('getWidth')
            ->willReturn($width);

        $this->imageModel
            ->expects($this->atLeastOnce())
            ->method('getHeight')
            ->willReturn($height);

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getTransformations')
            ->willReturn($transformations);

        $this->db
            ->expects($this->atLeastOnce())
            ->method('getBestMatch')
            ->with(
                $this->user,
                $this->imageIdentifier,
                $transformationWidth
            )
            ->willReturn(['width' => $variationWidth, 'height' => 600]);

        $this->eventManager
            ->expects($this->atLeastOnce())
            ->method('trigger')
            ->with(
                'image.transformations.adjust', [
                    'transformationIndex' => 1,
                    'ratio' => $width / $variationWidth,
                ]
            );

        $this->storage
            ->expects($this->atLeastOnce())
            ->method('getImageVariation')
            ->with(
                $this->user,
                $this->imageIdentifier,
                $variationWidth
            )
            ->willReturn(false);

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
        $width  = 1024;
        $height = 768;
        $transformationWidth = 512;
        $variationWidth = 800;
        $variationHeight = 600;
        $variationBlob = 'blob';
        $lastModified = new DateTime();
        $transformations = [[
            'name'   => 'desaturate',
            'params' => [],
        ],[
            'name'   => 'maxSize',
            'params' => ['width' => $transformationWidth]
        ]];

        $this->imageModel
            ->expects($this->any())
            ->method('getWidth')
            ->willReturn($width);

        $this->imageModel
            ->expects($this->any())
            ->method('getHeight')
            ->willReturn($height);

        $this->request
            ->expects($this->any())
            ->method('getTransformations')
            ->willReturn($transformations);

        $this->db
            ->expects($this->once())
            ->method('getBestMatch')
            ->with(
                $this->user,
                $this->imageIdentifier,
                $transformationWidth
            )
            ->willReturn(['width' => $variationWidth, 'height' => $variationHeight]);

        $this->eventManager
            ->expects($this->at(0))
            ->method('trigger')
            ->with('image.transformations.adjust', [
                'transformationIndex' => 1,
                'ratio' => $width / $variationWidth,
            ]);

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
                $variationWidth . 'x' . $variationHeight
            );

        $this->event
            ->expects($this->once())
            ->method('stopPropagation');

        $this->eventManager
            ->expects($this->at(1))
            ->method('trigger')
            ->with('image.loaded');

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

        $this->listener->deleteVariations($this->event);
    }

    /**
     * @covers ::generateVariations
     */
    public function testGenerateVariationsCallsStoreImageVariationForEveryWidth() : void {
        $listener = new ImageVariations([
            'database'  => ['adapter' => $this->db],
            'storage'   => ['adapter' => $this->storage],

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
            ->method('setWidth')
            ->willReturnSelf();

        $this->imageModel
            ->method('setHeight')
            ->willReturnSelf();

        $this->storage
             ->expects($this->exactly(6))
             ->method('storeImageVariation')
             ->with($this->user, $this->imageIdentifier, $this->anything(), $this->greaterThan(0));

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

        $this->imageModel->method('getWidth')->willReturn(2048);

        $this->transformation = $this->createConfiguredMock(Transformation::class, [
            'setImage' => $this->returnSelf(),
        ]);

        $this->transformation
            ->expects($this->at(1))
            ->method('transform')
            ->with(['type' => 'png']);

        $this->transformationManager->addTransformation('convert', $this->transformation);

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

        $this->imageModel
            ->method('getWidth')
            ->willReturn(2048);

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

        $this->imageModel
            ->method('getWidth')
            ->willReturn(2048);

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
        $this->imageModel->method('getWidth')->willReturn(1024);

        $this->transformation = $this->createConfiguredMock(Transformation::class, [
            'setImage' => $this->returnSelf(),
        ]);

        $this->transformation
            ->expects($this->at(1))
            ->method('transform')
            ->with($this->anything())
            ->willThrowException(new TransformationException());

        $this->transformationManager->addTransformation('resize', $this->transformation);

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

        $this->storage
            ->expects($this->once())
            ->method('storeImageVariation')
            ->willThrowException(new StorageException());

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

        $this->db
            ->expects($this->once())
            ->method('storeImageVariationMetadata')
            ->willThrowException(new DatabaseException());

        $this->expectWarning();
        $this->expectWarningMessage(
            'Could not store image variation metadata for user (imgid), width: 512'
        );

        $this->listener->generateVariations($this->event);
    }
}
