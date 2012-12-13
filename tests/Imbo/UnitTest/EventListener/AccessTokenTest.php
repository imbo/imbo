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

use Imbo\EventListener\AccessToken;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\EventListener\AccessToken
 */
class AccessTokenTest extends ListenerTests {
    /**
     * @var AccessToken
     */
    private $listener;

    private $event;
    private $request;
    private $query;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->query = $this->getMockBuilder('Imbo\Http\ParameterContainer')->disableOriginalConstructor()->getMock();

        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->request->expects($this->any())->method('getQuery')->will($this->returnValue($this->query));

        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $this->listener = new AccessToken();
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
        $this->query = null;
        $this->request = null;
        $this->event = null;
        $this->listener = null;
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Missing access token
     * @expectedExceptionCode 400
     * @covers Imbo\EventListener\AccessToken::invoke
     */
    public function testThrowsExceptionIfAnAccessTokenIsMissingFromTheRequestWhenNotWhitelisted() {
        $this->event->expects($this->once())->method('getName')->will($this->returnValue('image.get'));
        $this->query->expects($this->once())->method('has')->with('accessToken')->will($this->returnValue(false));

        $this->listener->invoke($this->event);
    }

    /**
     * Different filter combinations
     *
     * @return array[]
     */
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
    public function testSupportsFilters($filter, $transformations, $whitelisted) {
        $listener = new AccessToken($filter);

        if (!$whitelisted) {
            $this->setExpectedException('Imbo\Exception\RuntimeException', 'Missing access token', 400);
        }

        $this->event->expects($this->once())->method('getName')->will($this->returnValue('image.get'));
        $this->request->expects($this->any())->method('getTransformations')->will($this->returnValue($transformations));

        $listener->invoke($this->event);
    }

    /**
     * Get access tokens
     *
     * @return array[]
     */
    public function getAccessTokens() {
        return array(
            array(
                'http://imbo/users/christer',
                'some access token',
                'private key',
                false
            ),
            array(
                'http://imbo/users/christer',
                '81b52f01115401e5bcd0b65b625258510f8823e0b3189c13d279f84c4eb0ac3a',
                'private key',
                true
            ),
            array(
                'http://imbo/users/christer',
                '81b52f01115401e5bcd0b65b625258510f8823e0b3189c13d279f84c4eb0ac3a',
                'other private key',
                false
            ),
        );
    }

    /**
     * @dataProvider getAccessTokens
     * @covers Imbo\EventListener\AccessToken::invoke
     */
    public function testThrowsExceptionOnIncorrectToken($url, $token, $privateKey, $correct) {
        if (!$correct) {
            $this->setExpectedException('Imbo\Exception\RuntimeException', 'Incorrect access token', 400);
        }

        $this->query->expects($this->once())->method('has')->with('accessToken')->will($this->returnValue(true));
        $this->query->expects($this->once())->method('get')->with('accessToken')->will($this->returnValue($token));
        $this->request->expects($this->once())->method('getUrl')->will($this->returnValue($url));
        $this->request->expects($this->once())->method('getPrivateKey')->will($this->returnValue($privateKey));

        $this->listener->invoke($this->event);
    }
}
