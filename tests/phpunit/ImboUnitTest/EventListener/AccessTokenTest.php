<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener;

use Imbo\EventListener\AccessToken;

/**
 * @covers Imbo\EventListener\AccessToken
 * @group unit
 * @group listeners
 */
class AccessTokenTest extends ListenerTests {
    /**
     * @var AccessToken
     */
    private $listener;

    private $event;
    private $accessControl;
    private $request;
    private $response;
    private $responseHeaders;
    private $query;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->query = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');

        $this->accessControl = $this->getMock('Imbo\Auth\AccessControl\Adapter\AdapterInterface');

        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->request->query = $this->query;

        $this->responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->response->headers = $this->responseHeaders;

        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getAccessControl')->will($this->returnValue($this->accessControl));
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));

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
        $this->response = null;
        $this->responseHeaders = null;
        $this->event = null;
        $this->listener = null;
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Incorrect access token
     * @expectedExceptionCode 400
     * @covers Imbo\EventListener\AccessToken::checkAccessToken
     */
    public function testRequestWithBogusAccessToken() {
        $this->query->expects($this->once())->method('has')->with('accessToken')->will($this->returnValue(true));
        $this->query->expects($this->once())->method('get')->with('accessToken')->will($this->returnValue('/string'));
        $this->listener->checkAccessToken($this->event);
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Missing access token
     * @expectedExceptionCode 400
     * @covers Imbo\EventListener\AccessToken::checkAccessToken
     * @covers Imbo\EventListener\AccessToken::isWhitelisted
     */
    public function testThrowsExceptionIfAnAccessTokenIsMissingFromTheRequestWhenNotWhitelisted() {
        $this->event->expects($this->once())->method('getName')->will($this->returnValue('image.get'));
        $this->query->expects($this->once())->method('has')->with('accessToken')->will($this->returnValue(false));

        $this->listener->checkAccessToken($this->event);
    }

    /**
     * Different filter combinations
     *
     * @return array[]
     */
    public function getFilterData() {
        return array(
            'no filter and no transformations' => array(
                $filter = array(),
                $transformations = array(),
                $whitelisted = false,
            ),
            // @see https://github.com/imbo/imbo/issues/258
            'configured filters, but no transformations in the request' => array(
                $filter = array('transformations' => array('whitelist' => array('border'))),
                $transformations = array(),
                $whitelisted = false,
            ),
            array(
                $filter = array(),
                $transformations = array(
                    array('name' => 'border', 'params' => array()),
                ),
                $whitelisted = false,
            ),
            array(
                $filter = array('transformations' => array('whitelist' => array('border'))),
                $transformations = array(
                    array('name' => 'border', 'params' => array()),
                ),
                $whitelisted = true,
            ),
            array(
                $filter = array('transformations' => array('whitelist' => array('border'))),
                $transformations = array(
                    array('name' => 'border', 'params' => array()),
                    array('name' => 'thumbnail', 'params' => array()),
                ),
                $whitelisted = false,
            ),
            array(
                $filter = array('transformations' => array('blacklist' => array('border'))),
                $transformations = array(
                    array('name' => 'thumbnail', 'params' => array()),
                ),
                $whitelisted = true,
            ),
            array(
                $filter = array('transformations' => array('blacklist' => array('border'))),
                $transformations = array(
                    array('name' => 'border', 'params' => array()),
                    array('name' => 'thumbnail', 'params' => array()),
                ),
                $whitelisted = false,
            ),
            array(
                $filter = array('transformations' => array('whitelist' => array('border'), 'blacklist' => array('thumbnail'))),
                $transformations = array(
                    array('name' => 'border', 'params' => array()),
                ),
                $whitelisted = true,
            ),
            array(
                $filter = array('transformations' => array('whitelist' => array('border'), 'blacklist' => array('thumbnail'))),
                $transformations = array(
                    array('name' => 'canvas', 'params' => array()),
                ),
                $whitelisted = false,
            ),
            array(
                $filter = array('transformations' => array('whitelist' => array('border'), 'blacklist' => array('border'))),
                $transformations = array(
                    array('name' => 'border', 'params' => array()),
                ),
                $whitelisted = false,
            ),
        );
    }

    /**
     * @dataProvider getFilterData
     * @covers Imbo\EventListener\AccessToken::__construct
     * @covers Imbo\EventListener\AccessToken::checkAccessToken
     * @covers Imbo\EventListener\AccessToken::isWhitelisted
     */
    public function testSupportsFilters($filter, $transformations, $whitelisted) {
        $listener = new AccessToken($filter);

        if (!$whitelisted) {
            $this->setExpectedException('Imbo\Exception\RuntimeException', 'Missing access token', 400);
        }

        $this->event->expects($this->once())->method('getName')->will($this->returnValue('image.get'));
        $this->request->expects($this->any())->method('getTransformations')->will($this->returnValue($transformations));

        $listener->checkAccessToken($this->event);
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
            // Test that checking URL "as is" works properly. This is for backwards compatibility.
            array(
                'http://imbo/users/christer?t[]=thumbnail%3Awidth%3D40%2Cheight%3D40%2Cfit%3Doutbound',
                'f0166cb4f7c8eabbe82c5d753f681ed53bcfa10391d4966afcfff5806cc2bff4',
                'some random private key',
                true
            ),
        );
    }

    /**
     * @dataProvider getAccessTokens
     * @covers Imbo\EventListener\AccessToken::checkAccessToken
     */
    public function testThrowsExceptionOnIncorrectToken($url, $token, $privateKey, $correct) {
        if (!$correct) {
            $this->setExpectedException('Imbo\Exception\RuntimeException', 'Incorrect access token', 400);
        }

        $this->query->expects($this->once())->method('has')->with('accessToken')->will($this->returnValue(true));
        $this->query->expects($this->once())->method('get')->with('accessToken')->will($this->returnValue($token));
        $this->request->expects($this->once())->method('getRawUri')->will($this->returnValue(urldecode($url)));
        $this->request->expects($this->once())->method('getUriAsIs')->will($this->returnValue($url));

        $this->accessControl->expects($this->once())->method('getPrivateKey')->will($this->returnValue($privateKey));

        $this->listener->checkAccessToken($this->event);
    }

    /**
     * @covers Imbo\EventListener\AccessToken::checkAccessToken
     */
    public function testWillSkipValidationWhenShortUrlHeaderIsPresent() {
        $this->responseHeaders->expects($this->once())->method('has')->with('X-Imbo-ShortUrl')->will($this->returnValue(true));
        $this->query->expects($this->never())->method('has');
        $this->listener->checkAccessToken($this->event);
    }
}
