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
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\EventListener;

/**
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\EventListener\TransformationKey
 */
class TransformationKeyTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\EventListener\TransformationKey
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
     * @var Imbo\Http\ParameterContainerInterface
     */
    private $params;

    public function setUp() {
        $this->params = $this->getMock('Imbo\Http\ParameterContainerInterface');

        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->request->expects($this->any())->method('getQuery')->will($this->returnValue($this->params));

        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');

        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));

        $this->listener = new TransformationKey();
    }

    public function tearDown() {
        $this->params = null;
        $this->request = null;
        $this->response = null;
        $this->event = null;
        $this->listener = null;
    }

    /**
     * @covers Imbo\EventListener\TransformationKey::getEvents
     */
    public function testGetEvents() {
        $events = $this->listener->getEvents();
        $this->assertContains('image.get.pre', $events);
        $this->assertContains('image.head.pre', $events);
    }

    /**
     * @covers Imbo\EventListener\TransformationKey::invoke
     */
    public function testInvokeWithNoImageExtensionOrTransformations() {
        $this->request->expects($this->once())->method('getImageExtension')->will($this->returnValue(null));
        $this->assertNull($this->listener->invoke($this->event));
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Missing
     * @covers Imbo\EventListener\TransformationKey::invoke
     */
    public function testInvokeWithImageExtensionAndNoTransformations() {
        $this->request->expects($this->once())->method('getImageExtension')->will($this->returnValue('png'));
        $this->listener->invoke($this->event);
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Missing
     * @covers Imbo\EventListener\TransformationKey::invoke
     */
    public function testInvokeWithTransformationsAndNoImageExtension() {
        $this->request->expects($this->once())->method('getImageExtension')->will($this->returnValue(null));
        $this->params->expects($this->any())->method('has')->will($this->returnCallback(function($key) {
            if ($key === 't') {
                return true;
            } else if ($key === 'tk') {
                return false;
            }
        }));
        $this->listener->invoke($this->event);
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Invalid
     * @covers Imbo\EventListener\TransformationKey::invoke
     */
    public function testInvokeWithInvalidTransformationKey() {
        $this->request->expects($this->once())->method('getImageExtension')->will($this->returnValue(null));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue('publicKey'));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('imageIdentifier'));
        $this->request->expects($this->once())->method('getPrivateKey')->will($this->returnValue('privateKey'));

        $this->params->expects($this->any())->method('has')->will($this->returnValue(true));
        $this->params->expects($this->any())->method('get')->will($this->returnCallback(function($key) {
            if ($key === 'tk') {
                return 'invalid transformation key';
            } else if ($key === 't') {
                return array('thumbnail');
            }
        }));

        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\TransformationKey::invoke
     */
    public function testInvokeWithValidTransformationKeyWhenUsingAnExtensionAndTransformations() {
        $publicKey = 'publicKey';
        $privateKey = md5(microtime());
        $transformations = array(
            'thumbnail',
            'border',
        );
        $extension = 'png';
        $imageIdentifier = md5(microtime());

        // Create the transformation key
        $data = $publicKey . '|' . $imageIdentifier . '.' . $extension;
        $query = null;
        $query = array_reduce($transformations, function($query, $element) {
            return $query . 't[]=' . $element . '&';
        }, $query);

        $data .= '|' . rtrim($query, '&');
        $transformationKey = hash_hmac('md5', $data, $privateKey);

        $this->request->expects($this->once())->method('getImageExtension')->will($this->returnValue($extension));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $this->request->expects($this->once())->method('getPrivateKey')->will($this->returnValue($privateKey));

        $this->params->expects($this->any())->method('has')->will($this->returnValue(true));
        $this->params->expects($this->any())->method('get')->will($this->returnCallback(function($key) use ($transformationKey, $transformations) {
            if ($key === 'tk') {
                return $transformationKey;
            } else if ($key === 't') {
                return $transformations;
            }
        }));

        $this->listener->invoke($this->event);
    }
}
