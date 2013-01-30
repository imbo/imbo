<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Resource;

use Imbo\Resource\User;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class UserTest extends ResourceTests {
    /**
     * @var User
     */
    private $resource;

    private $request;
    private $response;
    private $database;
    private $storage;
    private $event;
    private $publicKey = 'key';

    /**
     * {@inheritdoc}
     */
    protected function getNewResource() {
        return new User();
    }

    /**
     * Set up the resource
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->storage = $this->getMock('Imbo\Storage\StorageInterface');
        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
        $this->event->expects($this->any())->method('getStorage')->will($this->returnValue($this->storage));

        $this->resource = $this->getNewResource();
    }

    /**
     * Tear down the resource
     */
    public function tearDown() {
        $this->resource = null;
        $this->response = null;
        $this->database = null;
        $this->storage = null;
        $this->event = null;
    }

    /**
     * @covers Imbo\Resource\User::get
     */
    public function testSupportsHttpGet() {
        $manager = $this->getMock('Imbo\EventManager\EventManager');
        $manager->expects($this->once())->method('trigger')->with('db.user.load');
        $this->event->expects($this->once())->method('getManager')->will($this->returnValue($manager));
        $this->response->expects($this->once())->method('getLastModified')->will($this->returnValue('some date'));
        $headers = $this->getMock('Imbo\Http\HeaderContainer');
        $headers->expects($this->once())->method('set')->with('ETag', '"73cc5b4252b4d06f472ff157a11fc208"');
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($headers));

        $this->resource->get($this->event);
    }
}
