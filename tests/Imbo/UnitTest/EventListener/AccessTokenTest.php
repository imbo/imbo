<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\UnitTest\EventListener;

use Imbo\EventListener\AccessToken,
    Imbo\Exception\RuntimeException;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\EventListener\AccessToken
 */
class AccessTokenTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\EventListener\AccessToken
     */
    private $listener;

    /**
     * @var Imbo\EventManager\EventInterface
     */
    private $event;

    /**
     * @var Imbo\Http\Request\RequestInterface
     */
    private $request;

    /**
     * @var Imbo\Http\Response\ResponseInterface
     */
    private $response;

    /**
     * @var Imbo\Container
     */
    private $container;

    /**
     * @var Imbo\Http\ParameterContainerInterface
     */
    private $params;

    public function setUp() {
        $this->params = $this->getMock('Imbo\Http\ParameterContainerInterface');

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->any())->method('getQuery')->will($this->returnValue($this->params));

        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');

        $this->container = $this->getMock('Imbo\Container');
        $this->container->expects($this->any())->method('get')->will($this->returnCallback(function($key) use($request, $response) {
            return $$key;
        }));

        $this->request = $request;
        $this->response = $response;

        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getContainer')->will($this->returnValue($this->container));

        $this->listener = new AccessToken();
    }

    public function tearDown() {
        $this->params = null;
        $this->request = null;
        $this->response = null;
        $this->event = null;
        $this->listener = null;
        $this->container = null;
    }

    /**
     * @covers Imbo\EventListener\AccessToken::getEvents
     */
    public function testGetEvents() {
        $events = $this->listener->getEvents();
        $expected = array(
            'user.get.pre',
            'images.get.pre',
            'image.get.pre',
            'metadata.get.pre',
            'user.head.pre',
            'images.head.pre',
            'image.head.pre',
            'metadata.head.pre',
        );

        foreach ($expected as $e) {
            $this->assertContains($e, $events);
        }
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Missing access token
     * @expectedExceptionCode 400
     * @covers Imbo\EventListener\AccessToken::invoke
     */
    public function testRequestWithoutAccessToken() {
        $this->params->expects($this->once())->method('has')->with('accessToken')->will($this->returnValue(false));
        $this->listener->invoke($this->event);
    }

    public function getFilterData() {
        return array(
            array(
                $filter = array(),
                $transformations = array(),
                $whitelisted = false,
            ),
            array(
                $filter = array(),
                $transformations = array(
                    array('name' => 'convert', 'params' => array()),
                ),
                $whitelisted = false,
            ),
            array(
                $filter = array('transformations' => array('whitelist' => array('convert'))),
                $transformations = array(
                    array('name' => 'convert', 'params' => array()),
                ),
                $whitelisted = true,
            ),
            array(
                $filter = array('transformations' => array('whitelist' => array('convert'))),
                $transformations = array(
                    array('name' => 'convert', 'params' => array()),
                    array('name' => 'border', 'params' => array()),
                ),
                $whitelisted = false,
            ),
            array(
                $filter = array('transformations' => array('blacklist' => array('convert'))),
                $transformations = array(
                    array('name' => 'border', 'params' => array()),
                ),
                $whitelisted = true,
            ),
            array(
                $filter = array('transformations' => array('blacklist' => array('convert'))),
                $transformations = array(
                    array('name' => 'convert', 'params' => array()),
                    array('name' => 'border', 'params' => array()),
                ),
                $whitelisted = false,
            ),
            array(
                $filter = array('transformations' => array('whitelist' => array('convert'), 'blacklist' => array('border'))),
                $transformations = array(
                    array('name' => 'convert', 'params' => array()),
                ),
                $whitelisted = true,
            ),
            array(
                $filter = array('transformations' => array('whitelist' => array('convert'), 'blacklist' => array('border'))),
                $transformations = array(
                    array('name' => 'canvas', 'params' => array()),
                ),
                $whitelisted = false,
            ),
            array(
                $filter = array('transformations' => array('whitelist' => array('convert'), 'blacklist' => array('convert'))),
                $transformations = array(
                    array('name' => 'convert', 'params' => array()),
                ),
                $whitelisted = false,
            ),
        );
    }

    /**
     * @dataProvider getFilterData
     * @covers Imbo\EventListener\AccessToken::invoke
     */
    public function testFilters($filter, $transformations, $whitelisted) {
        $listener = new AccessToken($filter);

        if (!$whitelisted) {
            $this->setExpectedException('Imbo\Exception\RuntimeException', 'Missing access token', 400);
        }

        $this->event->expects($this->once())->method('getName')->will($this->returnValue('image.get.pre'));
        $this->request->expects($this->any())->method('getTransformations')->will($this->returnValue($transformations));

        $listener->invoke($this->event);
    }
}
