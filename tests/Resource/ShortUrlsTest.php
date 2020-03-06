<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Image\OutputConverterManager;
use Imbo\Model\ArrayModel;

/**
 * @coversDefaultClass Imbo\Resource\ShortUrls
 */
class ShortUrlsTest extends ResourceTests {
    private $request;
    private $response;
    private $database;
    private $event;
    private $outputConverterManager;

    protected function getNewResource() : ShortUrls {
        return new ShortUrls();
    }

    public function setUp() : void {
        $this->resource = $this->getNewResource();
        $this->request = $this->createConfiguredMock(Request::class, [
            'getUser' => 'user',
            'getImageIdentifier' => 'id',
        ]);
        $this->response = $this->createMock(Response::class);
        $this->database = $this->createMock(DatabaseInterface::class);
        $this->outputConverterManager = $this->createConfiguredMock(OutputConverterManager::class, [
            'supportsExtension' => $this->returnCallback(function ($ext) { return $ext === 'gif'; }),
        ]);

        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getDatabase' => $this->database,
            'getOutputConverterManager' => $this->outputConverterManager,
        ]);
    }

    /**
     * @covers ::createShortUrl
     */
    public function testWillThrowAnExceptionWhenRequestBodyIsEmpty() : void {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn(null);
        $this->expectExceptionObject(new InvalidArgumentException('Missing JSON data', 400));
        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @covers ::createShortUrl
     */
    public function testWillThrowAnExceptionWhenRequestBodyIsInvalid() : void {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('some string');
        $this->expectExceptionObject(new InvalidArgumentException('Invalid JSON data', 400));
        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @covers ::createShortUrl
     */
    public function testWillThrowAnExceptionWhenUserMissing() : void {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{}');
        $this->expectExceptionObject(new InvalidArgumentException('Missing or invalid user', 400));
        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @covers ::createShortUrl
     */
    public function testWillThrowAnExceptionWhenUserDoesNotMatch() : void {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{"user": "otheruser"}');
        $this->expectExceptionObject(new InvalidArgumentException('Missing or invalid user', 400));
        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @covers ::createShortUrl
     */
    public function testWillThrowAnExceptionWhenImageIdentifierIsMissing() : void {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{"user": "user"}');
        $this->expectExceptionObject(new InvalidArgumentException('Missing or invalid image identifier', 400));
        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @covers ::createShortUrl
     */
    public function testWillThrowAnExceptionWhenImageIdentifierDoesNotMatch() : void {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{"user": "user", "imageIdentifier": "other id"}');
        $this->expectExceptionObject(new InvalidArgumentException('Missing or invalid image identifier', 400));
        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @covers ::createShortUrl
     */
    public function testWillThrowAnExceptionWhenExtensionIsNotRecognized() : void {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{"user": "user", "imageIdentifier": "id", "extension": "foo"}');
        $this->outputConverterManager
            ->expects($this->any())
            ->method('supportsExtension')
            ->willReturn(false);
        $this->expectExceptionObject(new InvalidArgumentException('Extension provided is not a recognized format', 400));
        $this->getNewResource()->createShortUrl($this->event);
    }

    public function createShortUrlParams() : array {
        return [
            'no extension, no query' => [
                null, null, [],
            ],
            'image extension, no query' => [
                'gif', null, [],
            ],
            'query string with leading ?' => [
                null, '?t[]=thumbnail:width=40,height=40&t[]=desaturate', [
                    't' => [
                        'thumbnail:width=40,height=40',
                        'desaturate',
                    ],
                ],
            ],
            'query string without leading ?' => [
                null, 't[]=thumbnail:width=40,height=40&t[]=desaturate', [
                    't' => [
                        'thumbnail:width=40,height=40',
                        'desaturate',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider createShortUrlParams
     * @covers ::createShortUrl
     * @covers ::getShortUrlId
     */
    public function testCanCreateShortUrls(?string $extension = null, ?string $queryString = null, array $query = []) : void {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('
            {
                "user": "user",
                "imageIdentifier": "id",
                "extension": ' . ($extension ? '"' . $extension . '"' : 'null') . ',
                "query": ' . ($queryString ? '"' . $queryString . '"' : 'null') . '
            }
        ');
        $this->database
            ->expects($this->once())
            ->method('imageExists')
            ->with('user', 'id')
            ->willReturn(true);
        $this->database
            ->expects($this->once())
            ->method('getShortUrlId')
            ->with('user', 'id', $extension, $query)
            ->willReturn(null);
        $this->database
            ->expects($this->once())
            ->method('getShortUrlParams')
            ->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'))
            ->willReturn(null);
        $this->database
            ->expects($this->once())
            ->method('insertShortUrl')
            ->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'), 'user', 'id', $extension, $query);
        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(ArrayModel::class))
            ->willReturnSelf();
        $this->response
            ->expects($this->once())
            ->method('setStatusCode')
            ->with(201);

        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @covers ::createShortUrl
     */
    public function testWillReturn200OKIfTheShortUrlAlreadyExists() : void {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('
                {
                    "user": "user",
                    "imageIdentifier": "id",
                    "extension": null,
                    "query": null
                }
            ');
        $this->database
            ->expects($this->once())
            ->method('imageExists')
            ->with('user', 'id')
            ->willReturn(true);
        $this->database
            ->expects($this->once())
            ->method('getShortUrlId')
            ->with('user', 'id', null, [])
            ->willReturn('aaaaaaa');
        $this->database
            ->expects($this->never())
            ->method('insertShortUrl');
        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(ArrayModel::class))
            ->willReturnSelf();
        $this->response
            ->expects($this->once())
            ->method('setStatusCode')
            ->with(200);

        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @covers ::createShortUrl
     */
    public function testWillGenerateANewIdIfTheGeneratedOneExists() : void {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('{"user": "user", "imageIdentifier": "id"}');

        $this->database
            ->expects($this->at(0))
            ->method('imageExists')
            ->with('user', 'id')
            ->willReturn(true);
        $this->database
            ->expects($this->at(1))
            ->method('getShortUrlId')
            ->with('user', 'id', null, [])
            ->willReturn(null);
        $this->database
            ->expects($this->at(2))
            ->method('getShortUrlParams')
            ->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'))
            ->willReturn(['user' => 'value']);
        $this->database
            ->expects($this->at(3))
            ->method('getShortUrlParams')
            ->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'))
            ->willReturn(['user' => 'value']);
        $this->database
            ->expects($this->at(4))
            ->method('getShortUrlParams')
            ->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'))
            ->willReturn(null);
        $this->database
            ->expects($this->at(5))
            ->method('insertShortUrl')
            ->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'), 'user', 'id', null, []);

        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(ArrayModel::class))
            ->willReturnSelf();
        $this->response
            ->expects($this->once())
            ->method('setStatusCode')
            ->with(201);

        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @covers ::deleteImageShortUrls
     */
    public function testWillNotAddAModelIfTheEventIsNotAShortUrlsEvent() : void {
        $this->database
            ->expects($this->once())
            ->method('deleteShortUrls')
            ->with('user', 'id');
        $this->event
            ->expects($this->once())
            ->method('getName')
            ->willReturn('image.delete');
        $this->response
            ->expects($this->never())
            ->method('setModel');

        $this->getNewResource()->deleteImageShortUrls($this->event);
    }

    /**
     * @covers ::deleteImageShortUrls
     */
    public function testWillAddAModelIfTheEventIsAShortUrlsEvent() : void {
        $this->database
            ->expects($this->once())
            ->method('deleteShortUrls')
            ->with('user', 'id');
        $this->event
            ->expects($this->once())
            ->method('getName')
            ->willReturn('shorturls.delete');
        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(ArrayModel::class));

        $this->getNewResource()->deleteImageShortUrls($this->event);
    }

    /**
     * @covers ::createShortUrl
     */
    public function testCanNotAddShortUrlWhenImageDoesNotExist() : void {
        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('
            {
                "user": "user",
                "imageIdentifier": "id",
                "extension": null,
                "query": null
            }
        ');
        $this->database
            ->expects($this->once())
            ->method('imageExists')
            ->with('user', 'id')
            ->willReturn(false);
        $this->expectExceptionObject(new InvalidArgumentException('Image does not exist', 404));
        $this->getNewResource()->createShortUrl($this->event);
    }
}
