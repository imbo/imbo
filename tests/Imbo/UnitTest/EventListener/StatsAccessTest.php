<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\EventListener;

use Imbo\EventListener\StatsAccess;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class StatsAccessTest extends ListenerTests {
    /**
     * @var StatsAccess
     */
    private $listener;

    private $event;
    private $request;

    /**
     * Set up the listener
     *
     * @covers Imbo\EventListener\StatsAccess::getDefinition
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\Request');

        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $this->listener = new StatsAccess();
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->request = null;
        $this->event = null;
        $this->listener = null;
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Access denied
     * @expectedExceptionCode 403
     * @covers Imbo\EventListener\StatsAccess::checkAccess
     */
    public function testDoesNotAllowAnyIpAddressPerDefault() {
        $this->listener->checkAccess($this->event);
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getFilterData() {
        return array(
            'single whitelisted valid ip' => array(
                '127.0.0.1',
                array('127.0.0.1'),
                array(),
                true
            ),
            'single whitelisted invalid ip' => array(
                '127.0.0.2',
                array('127.0.0.1'),
                array(),
                false
            ),
            'single blacklisted valid ip' => array(
                '127.0.0.2',
                array(),
                array('127.0.0.1'),
                true
            ),
            'single blacklisted invalid ip' => array(
                '127.0.0.1',
                array(),
                array('127.0.0.1'),
                false
            ),
            'whitelisted ip when both filters are used' => array(
                '127.0.0.1',
                array('127.0.0.1', '127.0.0.2'),
                array('127.0.0.3', '127.0.0.4'),
                true
            ),
            'blacklisted ip when both filters are used' => array(
                '127.0.0.3',
                array('127.0.0.1', '127.0.0.2'),
                array('127.0.0.3', '127.0.0.4'),
                false
            ),
            'client ip which exists in both filters' => array(
                '127.0.0.2',
                array('127.0.0.1', '127.0.0.2'),
                array('127.0.0.2', '127.0.0.3'),
                false
            ),
            'client ip does not exist in any filters' => array(
                '127.0.0.5',
                array('127.0.0.1', '127.0.0.2'),
                array('127.0.0.3', '127.0.0.4'),
                false
            ),
            'cidr notation with ip in whitelist range' => array(
                '192.168.1.10',
                array('192.168.1.0/24'),
                array(),
                true
            ),
            'cidr notation with ip in blacklist range' => array(
                '192.168.1.10',
                array(),
                array('192.168.1.0/24'),
                false
            ),
            'cidr notation with ip not in whitelist range' => array(
                '192.168.1.32',
                array('192.168.1.32/27'),
                array(),
                true
            ),
            'cidr notation with ip not in whitelist range' => array(
                '192.168.1.64',
                array('192.168.1.32/27'),
                array(),
                false
            ),
        );
    }

    /**
     * @dataProvider getFilterData
     * @covers Imbo\EventListener\StatsAccess::__construct
     * @covers Imbo\EventListener\StatsAccess::checkAccess
     * @covers Imbo\EventListener\StatsAccess::isWhitelisted
     * @covers Imbo\EventListener\StatsAccess::isBlacklisted
     */
    public function testCanUseDifferentFilters($clientIp, $whitelist, $blacklist, $hasAccess) {
        $this->request->expects($this->once())
                      ->method('getClientIp')
                      ->will($this->returnValue($clientIp));

        $listener = new StatsAccess(array(
            'whitelist' => $whitelist,
            'blacklist' => $blacklist,
        ));

        if (!$hasAccess) {
            $this->setExpectedException('Imbo\Exception\RuntimeException', 'Access denied', 403);
        }

        $listener->checkAccess($this->event);
    }
}
