<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\EventManager;

use Imbo\EventManager\EventManager,
    Imbo\EventManager\Event;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 * @covers Imbo\EventManager\EventManager
 */
class EventManagerTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var EventManager
     */
    private $manager;

    private $request;

    /**
     * Set up the event manager
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->manager = new EventManager($this->request);
        $this->manager->setEventTemplate(new Event());
    }

    /**
     * Tear down the event manager
     */
    public function tearDown() {
        $this->request = null;
        $this->manager = null;
    }

    /**
     * @covers Imbo\EventManager\EventManager::addEventHandler
     * @covers Imbo\EventManager\EventManager::addCallbacks
     * @covers Imbo\EventManager\EventManager::trigger
     */
    public function testCanRegisterAndExecuteRegularCallbacksInAPrioritizedFashion() {
        $callback1 = function ($event) { echo 1; };
        $callback2 = function ($event) { echo 2; };
        $callback3 = function ($event) { echo 3; };

        $this->assertSame(
            $this->manager,
            $this->manager->addEventHandler('handler1', $callback1)->addCallbacks('handler1', array('event1' => 0))
                          ->addEventHandler('handler2', $callback2)->addCallbacks('handler2', array('event2' => 1))
                          ->addEventHandler('handler3', $callback3)->addCallbacks('handler3', array('event2' => 2))
                          ->addEventHandler('handler4', $callback3)->addCallbacks('handler4', array('event3' => 0))
                          ->addEventHandler('handler5', $callback1)->addCallbacks('handler5', array('event4' => 0))
        );

        $this->expectOutputString('1321');

        $this->manager->trigger('otherevent')
                      ->trigger('event1')
                      ->trigger('event2')
                      ->trigger('event4');
    }

    /**
     * @covers Imbo\EventManager\EventManager::trigger
     */
    public function testLetsListenerStopPropagation() {
        $callback1 = function($event) { echo 1; };
        $callback2 = function($event) { echo 2; };
        $callback3 = function($event) { echo 3; };
        $stopper = function($event) {
            $event->stopPropagation(true);
        };

        $this->manager->addEventHandler('handler1', $callback1)->addCallbacks('handler1', array('event' => 3))
                      ->addEventHandler('handler2', $stopper)->addCallbacks('handler2', array('event' => 2))
                      ->addEventHandler('handler3', $callback2)->addCallbacks('handler3', array('event' => 1))
                      ->addEventHandler('handler4', $callback3)->addCallbacks('handler4', array('otherevent' => 0));

        $this->expectOutputString('13');

        $this->assertSame(
            $this->manager,
            $this->manager->trigger('event')
                          ->trigger('otherevent')
        );
    }

    /**
     * @covers Imbo\EventManager\EventManager::hasListenersForEvent
     */
    public function testCanCheckIfTheManagerHasListenersForSpecificEvents() {
        $this->manager->addEventHandler('handler', function($event) {})->addCallbacks('handler', array('event' => 0));
        $this->assertFalse($this->manager->hasListenersForEvent('some.event'));
        $this->assertTrue($this->manager->hasListenersForEvent('event'));
    }

    /**
     * Fetch public keys to test filtering
     *
     * @return array[]
     */
    public function getPublicKeys() {
        return array(
            array(null, array(), '1'),
            array(null, array('christer'), '1'),
            array('christer', array('blacklist' => array('christer', 'user')), ''),
            array('christer', array('blacklist' => array('user')), '1'),
            array('christer', array('whitelist' => array('user')), ''),
            array('christer', array('whitelist' => array('christer', 'user')), '1'),
        );
    }

    /**
     * @dataProvider getPublicKeys
     * @covers Imbo\EventManager\EventManager::hasListenersForEvent
     * @covers Imbo\EventManager\EventManager::triggersFor
     */
    public function testCanIncludeAndExcludePublicKeys($publicKey, $publicKeys, $output = '') {
        $callback = function ($event) { echo '1'; };

        $this->manager->addEventHandler('handler', $callback)->addCallbacks('handler', array('event' => 0), $publicKeys);

        $this->request->expects($this->any())->method('getPublicKey')->will($this->returnValue($publicKey));

        $this->expectOutputString($output);
        $this->manager->trigger('event');
    }
}
