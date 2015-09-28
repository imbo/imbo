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

use Imbo\EventListener\ImageVariations,
    Imbo\Exception\DatabaseException,
    Imbo\Exception\StorageException,
    Imbo\Exception\TransformationException,
    DateTime;

/**
 * @covers Imbo\EventListener\ImageVariations
 * @group unit
 * @group listeners
 */
class ImageVariationsTest extends ListenerTests {
    /**
     * @var ImageVariations
     */
    private $listener;

    private $db;
    private $storage;
    private $event;
    private $userLookup;
    private $request;
    private $response;
    private $responseHeaders;
    private $query;
    private $imageModel;
    private $imageStorage;
    private $eventManager;
    private $user = 'user';
    private $imageIdentifier = 'imgid';

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return new ImageVariations([
            'database' => [
                'adapter' => $this->db,
            ],
            'storage' => [
                'adapter' => $this->storage,
            ],
        ]);
    }

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->db = $this->getMock('Imbo\EventListener\ImageVariations\Database\DatabaseInterface');
        $this->storage = $this->getMock('Imbo\EventListener\ImageVariations\Storage\StorageInterface');

        $this->query = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');
        $this->imageModel = $this->getMock('Imbo\Model\Image');
        $this->eventManager = $this->getMock('Imbo\EventManager\EventManager');
        $this->imageStorage = $this->getMock('Imbo\Storage\StorageInterface');

        $this->imageModel->method('getImageIdentifier')->willReturn($this->imageIdentifier);

        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->request->expects($this->any())->method('getUser')->will($this->returnValue($this->user));
        $this->request->expects($this->any())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->request->expects($this->any())->method('getImage')->will($this->returnValue($this->imageModel));
        $this->request->query = $this->query;

        $this->responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->response->headers = $this->responseHeaders;
        $this->response->expects($this->any())->method('getModel')->will($this->returnValue($this->imageModel));

        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getManager')->will($this->returnValue($this->eventManager));
        $this->event->expects($this->any())->method('getStorage')->will($this->returnValue($this->imageStorage));

        $this->listener = $this->getListener();
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->listener = null;
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::__construct
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Scale factor must be below 1
     * @expectedExceptionCode 503
     */
    public function testThrowsOnInvalidScaleFactor() {
        return new ImageVariations([
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
     * @covers Imbo\EventListener\ImageVariations::__construct
     * @covers Imbo\EventListener\ImageVariations::configureDatabase
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Missing database adapter configuration for the image variations event listener
     * @expectedExceptionCode 500
     */
    public function testThrowsOnMissingDatabaseAdapter() {
        return new ImageVariations([
            'storage' => [ 'adapter' => $this->storage ],
        ]);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::__construct
     * @covers Imbo\EventListener\ImageVariations::configureDatabase
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid database adapter for the image variations event listener
     * @expectedExceptionCode 500
     */
    public function testThrowsOnInvalidDatabaseFromCallable() {
        return new ImageVariations([
            'database' => [ 'adapter' => function() { return null; } ],
            'storage'  => [ 'adapter' => $this->storage ],
        ]);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::__construct
     * @covers Imbo\EventListener\ImageVariations::configureDatabase
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid database adapter for the image variations event listener
     * @expectedExceptionCode 500
     */
    public function testThrowsOnInvalidDatabaseFromString() {
        return new ImageVariations([
            'database' => [ 'adapter' => 'DateTime' ],
            'storage'  => [ 'adapter' => $this->storage ],
        ]);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::__construct
     * @covers Imbo\EventListener\ImageVariations::configureStorage
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Missing storage adapter configuration for the image variations event listener
     * @expectedExceptionCode 500
     */
    public function testThrowsOnMissingStorageAdapter() {
        return new ImageVariations([
            'database' => [ 'adapter' => $this->db ],
        ]);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::__construct
     * @covers Imbo\EventListener\ImageVariations::configureStorage
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid storage adapter for the image variations event listener
     * @expectedExceptionCode 500
     */
    public function testThrowsOnInvalidStorageFromCallable() {
        return new ImageVariations([
            'storage'  => [ 'adapter' => function() { return null; } ],
            'database' => [ 'adapter' => $this->db ],
        ]);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::__construct
     * @covers Imbo\EventListener\ImageVariations::configureStorage
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid storage adapter for the image variations event listener
     * @expectedExceptionCode 500
     */
    public function testThrowsOnInvalidStorageFromString() {
        return new ImageVariations([
            'storage'  => [ 'adapter' => 'DateTime' ],
            'database' => [ 'adapter' => $this->db ],
        ]);
    }

    /**
     * @dataProvider getTransformations
     */
    public function testCanGetTheMinWidthFromASetOfTransformations($width, $height, array $transformations, $maxWidth) {
        $calculatedMax = $this->listener->getMaxWidth($width, $height, $transformations);

        $this->assertSame(
            is_null($maxWidth)      ? null : array_map('intval', $maxWidth),
            is_null($calculatedMax) ? null : array_map('intval', $calculatedMax),
            'Could not figure out the minimum width'
        );
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::adjustImageTransformations
     */
    public function testDoesNotAdjustTransformationsAfterGivenTransformationIndex() {
        $transformations = [[
            'name'   => 'desaturate',
            'params' => []
        ], [
            'name'   => 'border',
            'params' => ['width' => 5, 'height' => 5]
        ]];

        $this->event->method('getArgument')->will($this->returnValueMap([
            ['transformationIndex', 0],
            ['ratio', 0.25]
        ]));

        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue($transformations));
        $this->request->expects($this->once())->method('setTransformations')->with($this->equalTo($transformations));

        $this->listener->adjustImageTransformations($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::adjustImageTransformations
     * @dataProvider getAdjustmentTransformations
     */
    public function testAdjustsTransformationParams($transformations, $index, $ratio, $expectedIndex, $expected) {
        $this->event->method('getArgument')->will($this->returnValueMap([
            ['transformationIndex', $index],
            ['ratio', $ratio]
        ]));

        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue($transformations));
        $this->request->expects($this->once())->method('setTransformations')->with(
            $this->callback(function($adjusted) use ($expected, $expectedIndex) {
                $diff = array_diff($adjusted[$expectedIndex]['params'], $expected);
                return empty($diff);
            })
        );

        $this->listener->adjustImageTransformations($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::chooseVariation
     */
    public function testFallsBackIfNoTransformationsAreApplied() {
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue([]));
        $this->eventManager->expects($this->never())->method('trigger');

        $this->listener->chooseVariation($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::chooseVariation
     * @covers Imbo\EventListener\ImageVariations::getMaxWidth
     */
    public function testFallsBackIfNoRelevantTransformationsApplied() {
        $width  = 1024;
        $height = 768;
        $transformations = [[
            'name'   => 'desaturate',
            'params' => []
        ]];

        $this->imageModel->expects($this->once())->method('getWidth')->will($this->returnValue($width));
        $this->imageModel->expects($this->once())->method('getHeight')->will($this->returnValue($height));
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue($transformations));
        $this->eventManager->expects($this->never())->method('trigger');

        $this->listener->chooseVariation($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::chooseVariation
     * @covers Imbo\EventListener\ImageVariations::getMaxWidth
     */
    public function testFallsBackIfSizeIsLargerThanOriginal() {
        $width  = 1024;
        $height = 768;
        $transformations = [[
            'name'   => 'maxSize',
            'params' => ['width' => $width * 2]
        ]];

        $this->imageModel->expects($this->once())->method('getWidth')->will($this->returnValue($width));
        $this->imageModel->expects($this->once())->method('getHeight')->will($this->returnValue($height));
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue($transformations));
        $this->eventManager->expects($this->never())->method('trigger');

        $this->listener->chooseVariation($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::chooseVariation
     * @covers Imbo\EventListener\ImageVariations::getMaxWidth
     */
    public function testFallsBackIfDatabaseDoesNotReturnAnyVariation() {
        $width  = 1024;
        $height = 768;
        $transformations = [[
            'name'   => 'maxSize',
            'params' => ['width' => 512]
        ]];

        $this->eventManager->expects($this->never())->method('trigger');

        $this->imageModel->expects($this->once())->method('getWidth')->will($this->returnValue($width));
        $this->imageModel->expects($this->once())->method('getHeight')->will($this->returnValue($height));
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue($transformations));
        $this->db->expects($this->once())->method('getBestMatch')->with(
            $this->user,
            $this->imageIdentifier,
            512
        )->will($this->returnValue(null));

        $this->listener->chooseVariation($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::chooseVariation
     * @covers Imbo\EventListener\ImageVariations::getMaxWidth
     * @expectedException PHPUnit_Framework_Error
     * @expectedExceptionMessage Image variation storage is not in sync with the image variation database
     */
    public function testTriggersWarningIfVariationFoundInDbButNotStorage() {
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

        $this->imageModel->expects($this->atLeastOnce())->method('getWidth')->will($this->returnValue($width));
        $this->imageModel->expects($this->atLeastOnce())->method('getHeight')->will($this->returnValue($height));
        $this->request->expects($this->atLeastOnce())->method('getTransformations')->will($this->returnValue($transformations));
        $this->db->expects($this->atLeastOnce())->method('getBestMatch')->with(
            $this->user,
            $this->imageIdentifier,
            $transformationWidth
        )->will($this->returnValue(['width' => $variationWidth, 'height' => 600]));

        $this->eventManager->expects($this->atLeastOnce())->method('trigger')->with('image.transformations.adjust', [
            'transformationIndex' => 1,
            'ratio' => $width / $variationWidth,
        ]);

        $this->storage->expects($this->atLeastOnce())->method('getImageVariation')->with(
            $this->user,
            $this->imageIdentifier,
            $variationWidth
        )->will($this->returnValue(false));

        $this->imageStorage->expects($this->never())->method('getLastModified');

        // Running this twice, once with error suppresion (to flag "return" for code coverage),
        // once for triggering the warning
        @$this->listener->chooseVariation($this->event);
        $this->listener->chooseVariation($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::chooseVariation
     * @covers Imbo\EventListener\ImageVariations::getMaxWidth
     */
    public function testUpdatesResponseAndImageModelOnSuccess() {
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

        $this->imageModel->expects($this->once())->method('getWidth')->will($this->returnValue($width));
        $this->imageModel->expects($this->once())->method('getHeight')->will($this->returnValue($height));
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue($transformations));
        $this->db->expects($this->once())->method('getBestMatch')->with(
            $this->user,
            $this->imageIdentifier,
            $transformationWidth
        )->will($this->returnValue(['width' => $variationWidth, 'height' => $variationHeight]));

        $this->eventManager->expects($this->at(0))->method('trigger')->with('image.transformations.adjust', [
            'transformationIndex' => 1,
            'ratio' => $width / $variationWidth,
        ]);

        $this->storage->expects($this->once())->method('getImageVariation')->with(
            $this->user,
            $this->imageIdentifier,
            $variationWidth
        )->will($this->returnValue($variationBlob));

        $this->imageStorage->expects($this->once())->method('getLastModified')->with(
            $this->user,
            $this->imageIdentifier
        )->will($this->returnValue($lastModified));

        $this->response->expects($this->once())->method('setLastModified')->with($lastModified);

        $this->imageModel->expects($this->once())->method('setBlob')->with($variationBlob)->will($this->returnValue($this->imageModel));
        $this->imageModel->expects($this->once())->method('setWidth')->with($variationWidth)->will($this->returnValue($this->imageModel));
        $this->imageModel->expects($this->once())->method('setHeight')->with($variationHeight)->will($this->returnValue($this->imageModel));

        $this->responseHeaders->expects($this->once())->method('set')->with(
            'X-Imbo-ImageVariation',
            $variationWidth . 'x' . $variationHeight
        );

        $this->event->expects($this->once())->method('stopPropagation');
        $this->eventManager->expects($this->at(1))->method('trigger')->with('image.loaded');

        $this->listener->chooseVariation($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::deleteVariations
     * @expectedException PHPUnit_Framework_Error
     * @expectedExceptionMessage Could not delete image variation metadata for user (imgid)
     */
    public function testTriggersWarningOnFailedDeleteFromDatabase() {
        $this->db->expects($this->once())->method('deleteImageVariations')->with(
            $this->user,
            $this->imageIdentifier
        )->will($this->throwException(new DatabaseException()));

        $this->listener->deleteVariations($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::deleteVariations
     * @expectedException PHPUnit_Framework_Error
     * @expectedExceptionMessage Could not delete image variations from storage for user (imgid)
     */
    public function testTriggersWarningOnFailedDeleteFromStorage() {
        $this->storage->expects($this->once())->method('deleteImageVariations')->with(
            $this->user,
            $this->imageIdentifier
        )->will($this->throwException(new StorageException()));

        $this->listener->deleteVariations($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::deleteVariations
     */
    public function testDoesNotTriggerWarningsOnSuccessfulVariationsDelete() {
        $this->db->expects($this->once())->method('deleteImageVariations')->with(
            $this->user,
            $this->imageIdentifier
        );

        $this->storage->expects($this->once())->method('deleteImageVariations')->with(
            $this->user,
            $this->imageIdentifier
        );

        $this->listener->deleteVariations($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::generateVariations
     */
    public function testGenerateVariationsCallsStoreImageVariationForEveryWidth() {
        $listener = new ImageVariations([
            'database'  => [ 'adapter' => $this->db ],
            'storage'   => [ 'adapter' => $this->storage ],

            /**
             * With autoScale on, it should filter out the first and last two widths,
             * but as we have turned autoScale off, it should only remove the last width,
             * which is larger than our original.
             */
            'widths'    => [25, 100, 400, 800, 1024, 1700, 3000],
            'autoScale' => false,
        ]);

        $this->imageModel->method('getWidth')->willReturn(2048);

        $this->storage
             ->expects($this->exactly(6))
             ->method('storeImageVariation')
             ->with($this->user, $this->imageIdentifier, $this->anything(), $this->greaterThan(0));

        $listener->generateVariations($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::generateVariations
     */
    public function testGenerateVariationsWithLosslessParamTriggersPngConversion() {
        $listener = new ImageVariations([
            'database'  => [ 'adapter' => $this->db ],
            'storage'   => [ 'adapter' => $this->storage ],
            'widths'    => [500],
            'autoScale' => false,
            'lossless'  => true,
        ]);

        $this->imageModel->method('getWidth')->willReturn(2048);

        $this->eventManager
            ->expects($this->at(1))
            ->method('trigger')
            ->with('image.transformation.convert', [
                'image'  => $this->imageModel,
                'params' => [
                    'type' => 'png'
                ]
            ]);

        $listener->generateVariations($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::generateVariations
     */
    public function testGenerateVariationsAutoScalesRespectingMaxMinWidth() {
        $listener = new ImageVariations([
            'database'    => [ 'adapter' => $this->db ],
            'storage'     => [ 'adapter' => $this->storage ],
            'minDiff'     => 100,
            'minWidth'    => 320,
            'maxWidth'    => 1300,
            'scaleFactor' => .65,
        ]);

        $this->imageModel->method('getWidth')->willReturn(2048);

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
     * @covers Imbo\EventListener\ImageVariations::generateVariations
     */
    public function testGenerateVariationsIncludesSpecifiedWidths() {
        $listener = new ImageVariations([
            'database'    => [ 'adapter' => $this->db ],
            'storage'     => [ 'adapter' => $this->storage ],
            'widths'      => [ 1337 ],
            'scaleFactor' => .2,
        ]);

        $this->imageModel->method('getWidth')->willReturn(2048);

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
     * @covers Imbo\EventListener\ImageVariations::generateVariations
     * @expectedException PHPUnit_Framework_Error
     * @expectedExceptionMessage Could not generate image variation for user (imgid), width: 512
     */
    public function testGenerateVariationsTriggersWarningOnTransformationException() {
        $this->imageModel->method('getWidth')->willReturn(1024);

        $this->eventManager->expects($this->at(1))
            ->method('trigger')
            ->with('image.transformation.resize', $this->anything())
            ->will($this->throwException(new TransformationException()));

        $this->listener->generateVariations($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::generateVariations
     * @expectedException PHPUnit_Framework_Error
     * @expectedExceptionMessage Could not store image variation for user (imgid), width: 512
     */
    public function testGenerateVariationsTriggersWarningOnStorageException() {
        $this->imageModel->method('getWidth')->willReturn(1024);

        $this->storage->expects($this->once())
            ->method('storeImageVariation')
            ->will($this->throwException(new StorageException()));

        $this->listener->generateVariations($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations::generateVariations
     * @expectedException PHPUnit_Framework_Error
     * @expectedExceptionMessage Could not store image variation metadata for user (imgid), width: 512
     */
    public function testGenerateVariationsTriggersWarningOnDatabaseException() {
        $this->imageModel->method('getWidth')->willReturn(1024);

        $this->db->expects($this->once())
            ->method('storeImageVariationMetadata')
            ->will($this->throwException(new DatabaseException()));

        $this->listener->generateVariations($this->event);
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getAdjustmentTransformations() {
        return [
            'crop' => [
                [
                    [
                        'name'   => 'crop',
                        'params' => ['width' => 5, 'height' => 10, 'x' => 15, 'y' => 20]
                    ], [
                        'name'   => 'desaturate',
                        'params' => []
                    ]
                ],
                1,   // Index
                1.5, // Ratio
                1,   // Expected index
                [    // Expected adjusted values
                    'width'  => 3,
                    'height' => 7,
                    'x'      => 10,
                    'y'      => 13,
                ]
            ],
            'border' => [
                [
                    [
                        'name'   => 'border',
                        'params' => ['width' => 5, 'height' => 10]
                    ]
                ],
                0,   // Index
                1.5, // Ratio
                0,   // Expected index
                [    // Expected adjusted values
                    'width'  => 3,
                    'height' => 7
                ]
            ],
            'canvas' => [
                [
                    [
                        'name'   => 'canvas',
                        'params' => ['width' => 20, 'height' => 15, 'x' => 10, 'y' => 5]
                    ]
                ],
                0,   // Index
                1.5, // Ratio
                0,   // Expected index
                [    // Expected adjusted values
                    'width'  => 13,
                    'height' => 10,
                    'x'      => 7,
                    'y'      => 3,
                ]
            ],
            'watermark' => [
                [
                    [
                        'name'   => 'watermark',
                        'params' => ['width' => 20, 'height' => 15, 'x' => 10, 'y' => 5]
                    ]
                ],
                0,   // Index
                1.5, // Ratio
                0,   // Expected index
                [    // Expected adjusted values
                    'width'  => 13,
                    'height' => 10,
                    'x'      => 7,
                    'y'      => 3,
                ]
            ],
        ];
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getTransformations() {
        return [
            'no transformations' => [
                800,
                600,
                [],
                null,
            ],
            'resize with width' => [
                800,
                600,
                [
                    [
                        'name' => 'resize',
                        'params' => [
                            'width' => 200,
                        ],
                    ],
                ],
                [200],
            ],
            'resize with height' => [
                800,
                600,
                [
                    [
                        'name' => 'resize',
                        'params' => [
                            'height' => 200,
                        ],
                    ],
                ],
                [200 * (800 / 600)], // height * aspect ratio
            ],
            'maxSize with width' => [
                1024,
                768,
                [
                    [
                        'name' => 'maxSize',
                        'params' => [
                            'width' => 150,
                        ],
                    ],
                ],
                [150],
            ],
            'maxSize with height' => [
                1024,
                768,
                [
                    [
                        'name' => 'maxSize',
                        'params' => [
                            'height' => 150,
                        ],
                    ],
                ],
                [150 * (1024 / 768)], // height * aspect ratio
            ],
            'thumbnail with width' => [
                500,
                500,
                [
                    [
                        'name' => 'thumbnail',
                        'params' => [
                            'width' => 25,
                        ],
                    ],
                ],
                [25],
            ],
            'thumbnail with height' => [
                500,
                500,
                [
                    [
                        'name' => 'thumbnail',
                        'params' => [
                            'height' => 25,
                        ],
                    ],
                ],
                [50], // default for thumbnail
            ],
            'thumbnail with height and inset fit' => [
                500,
                500,
                [
                    [
                        'name' => 'thumbnail',
                        'params' => [
                            'height' => 25,
                            'fit' => 'inset',
                        ],
                    ],
                ],
                [25 * (500 / 500)], // height * aspect ratio
            ],
            'thumbnail with no params' => [
                500,
                500,
                [
                    [
                        'name' => 'thumbnail',
                        'params' => [],
                    ],
                ],
                [50], // default for thumbnail
            ],
            'pick a value that is not in the first index' => [
                800,
                600,
                [
                    [
                        'name' => 'thumbnail',
                        'params' => [],
                    ],
                    [
                        'name' => 'resize',
                        'params' => [
                            'width' => 250,
                        ],
                    ],
                    [
                        'name' => 'maxSize',
                        'params' => [
                            'width' => 100,
                        ],
                    ],
                ],
                [1 => 250],
            ],
            'maxsize + crop' => [
                1024,
                768,
                [
                    [
                        'name' => 'maxSize',
                        'params' => ['width' => 600],
                    ],
                    [
                        'name' => 'crop',
                        'params' => ['width' => 100, 'height' => 100, 'x' => 924, 'y' => 668],
                    ],
                ],
                [600],
            ],
            'crop + maxsize' => [
                1024,
                768,
                [
                    [
                        'name' => 'crop',
                        'params' => ['width' => 500, 'height' => 500, 'x' => 262, 'y' => 134],
                    ],
                    [
                        'name' => 'maxSize',
                        'params' => ['width' => 250],
                    ],
                ],
                [512],
            ],
        ];
    }
}
