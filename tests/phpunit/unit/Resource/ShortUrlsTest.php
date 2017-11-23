<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Resource;

use Imbo\Resource\ShortUrls;
use Imbo\Exception\InvalidArgumentException;

/**
 * @covers Imbo\Resource\ShortUrls
 * @group unit
 * @group resources
 */
class ShortUrlsTest extends ResourceTests {
    /**
     * @var ShortUrls
     */
    private $resource;

    private $request;
    private $response;
    private $database;
    private $event;
    private $outputConverterManager;

    /**
     * {@inheritdoc}
     */
    protected function getNewResource() {
        return new ShortUrls();
    }

    /**
     * Set up the resource
     */
    public function setUp() {
        $this->resource = $this->getNewResource();
        $this->request = $this->createMock('Imbo\Http\Request\Request');
        $this->response = $this->createMock('Imbo\Http\Response\Response');
        $this->database = $this->createMock('Imbo\Database\DatabaseInterface');
        $this->event = $this->createMock('Imbo\EventManager\Event');
        $this->outputConverterManager = $this->createMock('Imbo\Image\OutputConverterManager');
        $this->outputConverterManager->expects($this->any())->method('supportsExtension')->will($this->returnCallback(function ($ext) { return $ext == 'gif'; }));

        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
        $this->event->expects($this->any())->method('getOutputConverterManager')->will($this->returnValue($this->outputConverterManager));
    }

    public function testWillThrowAnExceptionWhenRequestBodyIsEmpty() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(null));
        $this->expectExceptionObject(new InvalidArgumentException('Missing JSON data', 400));
        $this->getNewResource()->createShortUrl($this->event);
    }

    public function testWillThrowAnExceptionWhenRequestBodyIsInvalid() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('some string'));
        $this->expectExceptionObject(new InvalidArgumentException('Invalid JSON data', 400));
        $this->getNewResource()->createShortUrl($this->event);
    }

    public function testWillThrowAnExceptionWhenUserMissing() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{}'));
        $this->expectExceptionObject(new InvalidArgumentException('Missing or invalid user', 400));
        $this->getNewResource()->createShortUrl($this->event);
    }

    public function testWillThrowAnExceptionWhenUserDoesNotMatch() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"user": "user"}'));
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('otheruser'));
        $this->expectExceptionObject(new InvalidArgumentException('Missing or invalid user', 400));
        $this->getNewResource()->createShortUrl($this->event);
    }

    public function testWillThrowAnExceptionWhenImageIdentifierIsMissing() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"user": "user"}'));
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->expectExceptionObject(new InvalidArgumentException('Missing or invalid image identifier', 400));
        $this->getNewResource()->createShortUrl($this->event);
    }

    public function testWillThrowAnExceptionWhenImageIdentifierDoesNotMatch() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"user": "user", "imageIdentifier": "id"}'));
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('other id'));
        $this->expectExceptionObject(new InvalidArgumentException('Missing or invalid image identifier', 400));
        $this->getNewResource()->createShortUrl($this->event);
    }

    public function testWillThrowAnExceptionWhenExtensionIsNotRecognized() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"user": "user", "imageIdentifier": "id", "extension": "foo"}'));
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->outputConverterManager->expects($this->any())->method('supportsExtension')->will($this->returnValue(false));
        $this->expectExceptionObject(new InvalidArgumentException('Extension provided is not a recognized format', 400));
        $this->getNewResource()->createShortUrl($this->event);
    }

    public function createShortUrlParams() {
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
     */
    public function testCanCreateShortUrls($extension = null, $queryString = null, array $query = []) {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('
            {
                "user": "user",
                "imageIdentifier": "id",
                "extension": ' . ($extension ? '"' . $extension . '"' : 'null') . ',
                "query": ' . ($queryString ? '"' . $queryString . '"' : 'null') . '
            }
        '));
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->database->expects($this->once())->method('imageExists')->with('user', 'id')->will($this->returnValue(true));
        $this->database->expects($this->once())->method('getShortUrlId')->with('user', 'id', $extension, $query)->will($this->returnValue(null));
        $this->database->expects($this->once())->method('getShortUrlParams')->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'))->will($this->returnValue(null));
        $this->database->expects($this->once())->method('insertShortUrl')->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'), 'user', 'id', $extension, $query);
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'))->will($this->returnSelf());
        $this->response->expects($this->once())->method('setStatusCode')->with(201);

        $this->getNewResource()->createShortUrl($this->event);
    }

    public function testWillReturn200OKIfTheShortUrlAlreadyExists() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('
            {
                "user": "user",
                "imageIdentifier": "id",
                "extension": null,
                "query": null
            }
        '));
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->database->expects($this->once())->method('imageExists')->with('user', 'id')->will($this->returnValue(true));
        $this->database->expects($this->once())->method('getShortUrlId')->with('user', 'id', null, [])->will($this->returnValue('aaaaaaa'));
        $this->database->expects($this->never())->method('insertShortUrl');
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'))->will($this->returnSelf());
        $this->response->expects($this->once())->method('setStatusCode')->with(200);

        $this->getNewResource()->createShortUrl($this->event);
    }

    public function testWillGenerateANewIdIfTheGeneratedOneExists() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"user": "user", "imageIdentifier": "id"}'));
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));

        $this->database->expects($this->at(0))->method('imageExists')->with('user', 'id')->will($this->returnValue(true));
        $this->database->expects($this->at(1))->method('getShortUrlId')->with('user', 'id', null, [])->will($this->returnValue(null));
        $this->database->expects($this->at(2))->method('getShortUrlParams')->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'))->will($this->returnValue(['user' => 'value']));
        $this->database->expects($this->at(3))->method('getShortUrlParams')->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'))->will($this->returnValue(['user' => 'value']));
        $this->database->expects($this->at(4))->method('getShortUrlParams')->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'))->will($this->returnValue(null));
        $this->database->expects($this->at(5))->method('insertShortUrl')->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'), 'user', 'id', null, []);

        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'))->will($this->returnSelf());
        $this->response->expects($this->once())->method('setStatusCode')->with(201);

        $this->getNewResource()->createShortUrl($this->event);
    }

    public function testWillNotAddAModelIfTheEventIsNotAShortUrlsEvent() {
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->database->expects($this->once())->method('deleteShortUrls')->with('user', 'id');
        $this->event->expects($this->once())->method('getName')->will($this->returnValue('image.delete'));
        $this->response->expects($this->never())->method('setModel');

        $this->getNewResource()->deleteImageShortUrls($this->event);
    }

    public function testWillAddAModelIfTheEventIsAShortUrlsEvent() {
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->database->expects($this->once())->method('deleteShortUrls')->with('user', 'id');
        $this->event->expects($this->once())->method('getName')->will($this->returnValue('shorturls.delete'));
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'));

        $this->getNewResource()->deleteImageShortUrls($this->event);
    }

    public function testCanNotAddShortUrlWhenImageDoesNotExist() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('
            {
                "user": "user",
                "imageIdentifier": "id",
                "extension": null,
                "query": null
            }
        '));
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->database->expects($this->once())->method('imageExists')->with('user', 'id')->will($this->returnValue(false));
        $this->expectExceptionObject(new InvalidArgumentException('Image does not exist', 404));
        $this->getNewResource()->createShortUrl($this->event);
    }
}
