<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventManager;

use Imbo\EventManager\Event,
    Imbo\EventManager\EventManagerInterface,
    Imbo\Http\Request\Request,
    Imbo\Http\Response\Response,
    Imbo\Database\DatabaseInterface,
    Imbo\Storage\StorageInterface;

/**
 * @covers Imbo\EventManager\Event
 * @group unit
 * @group eventmanager
 */
class EventTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Event
     */
    private $event;

    /**
     * Set up the event instance
     */
    public function setUp() {
        $this->event = new Event();
    }

    /**
     * Set up the event instance
     */
    public function tearDown() {
        $this->event = null;
    }

    public function getArguments() {
        return array(
            'request' => array(
                'getRequest', 'request', $this->getMock('Imbo\Http\Request\Request'),
            ),
            'response' => array(
                'getResponse', 'response', $this->getMock('Imbo\Http\Response\Response'),
            ),
            'database' => array(
                'getDatabase', 'database', $this->getMock('Imbo\Database\DatabaseInterface'),
            ),
            'storage' => array(
                'getStorage', 'storage', $this->getMock('Imbo\Storage\StorageInterface'),
            ),
            'accessControl' => array(
                'getAccessControl', 'accessControl', $this->getMock('Imbo\Auth\AccessControl\Adapter\AdapterInterface'),
            ),
            'manager' => array(
                'getManager', 'manager', $this->getMockBuilder('Imbo\EventManager\EventManager')->disableOriginalConstructor()->getMock(),
            ),
            'config' => array(
                'getConfig', 'config', array('some' => 'config'),
            ),
            'handler' => array(
                'getHandler', 'handler', 'handler name',
            ),
        );
    }

    /**
     * @dataProvider getArguments
     */
    public function testCanSetAndGetRequest($method, $argument, $value) {
        $this->event->setArgument($argument, $value);
        $this->assertSame($value, $this->event->$method());
    }
}
