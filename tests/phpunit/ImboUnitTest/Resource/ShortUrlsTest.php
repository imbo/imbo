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
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->event = $this->getMock('Imbo\EventManager\Event');

        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
    }

    /**
     * Tear down the resource
     */
    public function tearDown() {
        $this->resource = null;
        $this->request = null;
        $this->response = null;
        $this->database = null;
        $this->event = null;
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing JSON data
     * @expectedExceptionCode 400
     */
    public function testWillThrowAnExceptionWhenRequestBodyIsEmpty() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(null));
        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid JSON data
     * @expectedExceptionCode 400
     */
    public function testWillThrowAnExceptionWhenRequestBodyIsInvalid() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('some string'));
        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing or invalid user
     * @expectedExceptionCode 400
     */
    public function testWillThrowAnExceptionWhenUserMissing() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{}'));
        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing or invalid user
     * @expectedExceptionCode 400
     */
    public function testWillThrowAnExceptionWhenUserDoesNotMatch() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"user": "user"}'));
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('otheruser'));
        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing or invalid image identifier
     * @expectedExceptionCode 400
     */
    public function testWillThrowAnExceptionWhenImageIdentifierIsMissing() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"user": "user"}'));
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing or invalid image identifier
     * @expectedExceptionCode 400
     */
    public function testWillThrowAnExceptionWhenImageIdentifierDoesNotMatch() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"user": "user", "imageIdentifier": "id"}'));
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('other id'));
        $this->getNewResource()->createShortUrl($this->event);
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Extension provided is not a recognized format
     * @expectedExceptionCode 400
     */
    public function testWillThrowAnExceptionWhenExtensionIsNotRecognized() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"user": "user", "imageIdentifier": "id", "extension": "foo"}'));
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->getNewResource()->createShortUrl($this->event);
    }

    public function createShortUrlParams() {
        return array(
            'no extension, no query' => array(
                null, null, array(),
            ),
            'image extension, no query' => array(
                'gif', null, array(),
            ),
            'query string with leading ?' => array(
                null, '?t[]=thumbnail:width=40,height=40&t[]=desaturate', array(
                    't' => array(
                        'thumbnail:width=40,height=40',
                        'desaturate',
                    ),
                ),
            ),
            'query string without leading ?' => array(
                null, 't[]=thumbnail:width=40,height=40&t[]=desaturate', array(
                    't' => array(
                        'thumbnail:width=40,height=40',
                        'desaturate',
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider createShortUrlParams
     */
    public function testCanCreateShortUrls($extension = null, $queryString = null, array $query = array()) {
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
        $this->database->expects($this->once())->method('getShortUrlId')->with('user', 'id', null, array())->will($this->returnValue('aaaaaaa'));
        $this->database->expects($this->never())->method('insertShortUrl');
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'))->will($this->returnSelf());
        $this->response->expects($this->once())->method('setStatusCode')->with(200);

        $this->getNewResource()->createShortUrl($this->event);
    }

    public function testWillGenerateANewIdIfTheGeneratedOneExists() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue('{"user": "user", "imageIdentifier": "id"}'));
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));

        $this->database->expects($this->at(0))->method('getShortUrlId')->with('user', 'id', null, array())->will($this->returnValue(null));
        $this->database->expects($this->at(1))->method('getShortUrlParams')->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'))->will($this->returnValue(array('user' => 'value')));
        $this->database->expects($this->at(2))->method('getShortUrlParams')->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'))->will($this->returnValue(array('user' => 'value')));
        $this->database->expects($this->at(3))->method('getShortUrlParams')->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'))->will($this->returnValue(null));
        $this->database->expects($this->at(4))->method('insertShortUrl')->with($this->matchesRegularExpression('/[a-zA-Z0-9]{7}/'), 'user', 'id', null, array());

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
}
