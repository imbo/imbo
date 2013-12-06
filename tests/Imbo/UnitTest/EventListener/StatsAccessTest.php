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
 * @group unit
 * @group listeners
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
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\Request');

        $this->event = $this->getMock('Imbo\EventManager\Event');
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
            'IPv4 in whitelist' => array(
                '127.0.0.1',        // IP
                array('127.0.0.1'), // Whitelist
                array(),            // Blacklist
                true                // Access?
            ),
            'IPv4 not in whitelist' => array(
                '127.0.0.2',
                array('127.0.0.1'),
                array(),
                false
            ),
            'IPv4 not in blacklist' => array(
                '127.0.0.2',
                array(),
                array('127.0.0.1'),
                true
            ),
            'IPv4 in blacklist' => array(
                '127.0.0.1',
                array(),
                array('127.0.0.1'),
                false
            ),
            'IPv4 in whitelist with both filters populated' => array(
                '127.0.0.1',
                array('127.0.0.1', '127.0.0.2'),
                array('127.0.0.3', '127.0.0.4'),
                true
            ),
            'IPv4 in blacklist with both filters populated' => array(
                '127.0.0.3',
                array('127.0.0.1', '127.0.0.2'),
                array('127.0.0.3', '127.0.0.4'),
                false
            ),
            'IPv4 in both lists with both filters populated' => array(
                '127.0.0.2',
                array('127.0.0.1', '127.0.0.2'),
                array('127.0.0.2', '127.0.0.3'),
                false
            ),
            'IPv4 in no lists with both filters populated' => array(
                '127.0.0.5',
                array('127.0.0.1', '127.0.0.2'),
                array('127.0.0.3', '127.0.0.4'),
                false
            ),
            'IPv4 in whitelist range' => array(
                '192.168.1.10',
                array('192.168.1.0/24'),
                array(),
                true
            ),
            'IPv4 in blacklist range' => array(
                '192.168.1.10',
                array(),
                array('192.168.1.0/24'),
                false
            ),
            'IPv4 outside of whitelist range' => array(
                '192.168.1.64',
                array('192.168.1.32/27'),
                array(),
                false
            ),
            'IPv6 in whitelist (in short format)' => array(
                '2a00:1b60:1011:0000:0000:0000:0000:1338',
                array('2a00:1b60:1011::1338'),
                array(),
                true
            ),
            'IPv6 in whitelist (in full format)' => array(
                '2a00:1b60:1011:0000:0000:0000:0000:1338',
                array('2a00:1b60:1011:0000:0000:0000:0000:1338'),
                array(),
                true
            ),
            'IPv6 in whitelist range' => array(
                '2001:0db8:0000:0000:0000:0000:0000:0000',
                array('2001:db8::/48'),
                array(),
                true
            ),
            'IPv6 outside of whitelist range' => array(
                '2001:0db9:0000:0000:0000:0000:0000:0000',
                array('2001:db8::/48'),
                array(),
                false
            ),
            'IPv6 in whitelist (in short format in both fields)' => array(
                '2a00:1b60:1011::1338',
                array('2a00:1b60:1011::1338'),
                array(),
                true
            ),
        );
    }

    /**
     * @dataProvider getFilterData
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
