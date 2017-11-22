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

use Imbo\Resource\User;
use DateTime;
use DateTimeZone;

/**
 * @covers Imbo\Resource\User
 * @group unit
 * @group resources
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
        $this->request = $this->createMock('Imbo\Http\Request\Request');
        $this->response = $this->createMock('Imbo\Http\Response\Response');
        $this->database = $this->createMock('Imbo\Database\DatabaseInterface');
        $this->storage = $this->createMock('Imbo\Storage\StorageInterface');
        $this->event = $this->createMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
        $this->event->expects($this->any())->method('getStorage')->will($this->returnValue($this->storage));

        $this->resource = $this->getNewResource();
    }

    /**
     * @covers Imbo\Resource\User::get
     */
    public function testSupportsHttpGet() {
        $date = new DateTime('@1361628679', new DateTimeZone('UTC'));
        $manager = $this->createMock('Imbo\EventManager\EventManager');
        $manager->expects($this->once())->method('trigger')->with('db.user.load');
        $this->event->expects($this->once())->method('getManager')->will($this->returnValue($manager));

        $this->resource->get($this->event);
    }
}
