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

namespace Imbo\Validate;

/**
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Validate\Signature
 */
class SignatureTest extends \PHPUnit_Framework_TestCase {
    private $validate;

    public function setUp() {
        $this->validate = new Signature();
    }

    public function tearDown() {
        $this->validate = null;
    }

    /**
     * @covers Imbo\Validate\Signature::setHttpMethod
     */
    public function testSetHttpMethod() {
        $this->assertSame($this->validate, $this->validate->setHttpMethod('POST'));
    }

    /**
     * @covers Imbo\Validate\Signature::setUrl
     */
    public function testSetUrl() {
        $this->assertSame($this->validate, $this->validate->setUrl('http://imbo/users/'));
    }

    /**
     * @covers Imbo\Validate\Signature::setTimestamp
     */
    public function testSetTimestamp() {
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $this->assertSame($this->validate, $this->validate->setTimestamp($timestamp));
    }

    /**
     * @covers Imbo\Validate\Signature::setPublicKey
     */
    public function testSetPublicKey() {
        $key = 'e047e85fbfc6e212beb4b9bbc025b019';
        $this->assertSame($this->validate, $this->validate->setPublicKey($key));
    }

    /**
     * @covers Imbo\Validate\Signature::setPrivateKey
     */
    public function testSetPrivateKey() {
        $key = 'e047e85fbfc6e212beb4b9bbc025b019';
        $this->assertSame($this->validate, $this->validate->setPrivateKey($key));
    }

    /**
     * @covers Imbo\Validate\Signature::isValid
     */
    public function testIsValid() {
        $method     = 'POST';
        $publicKey  = 'e047e85fbfc6e212beb4b9bbc025b019';
        $privateKey = '8964caf20f7bfc5237113c41734c7f76';
        $image      = '9a7f7f58982fe079c55f2307f0c3b93d';
        $url        = 'http://imbo/users/' . $publicKey . '/images/' . $image;
        $timestamp  = '2011-11-28T08:43:20Z';
        $signature  = '4bc29f267db3df1717b0803c4e69dd9ee5620de7e99703d82335973e7f31d273';

        $this->validate->setHttpMethod($method)->setUrl($url)->setTimestamp($timestamp)->setPublicKey($publicKey)->setPrivateKey($privateKey);
        $this->assertTrue($this->validate->isValid($signature));
        $this->validate->setHttpMethod('PUT');
        $this->assertFalse($this->validate->isValid($signature));
    }
}
