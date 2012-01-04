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
 * @package Validators
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Validate;

/**
 * Signature generator
 *
 * @package Validators
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class Signature implements SignatureInterface {
    /**
     * The HTTP method to use
     *
     * @var string
     */
    private $httpMethod;

    /**
     * The URL to use
     *
     * @var string
     */
    private $url;

    /**
     * The timestamp to ues
     *
     * @var string
     */
    private $timestamp;

    /**
     * The public key to use
     *
     * @var string
     */
    private $publicKey;

    /**
     * The private key to use
     *
     * @var string
     */
    private $privateKey;

    /**
     * The algorithm to use with the hash_hmac() function
     *
     * @var string
     */
    protected $algo = 'sha256';

    /**
     * @see Imbo\Validate\SignatureInterface::setHttpMethod()
     */
    public function setHttpMethod($method) {
        $this->httpMethod = $method;

        return $this;
    }

    /**
     * @see Imbo\Validate\SignatureInterface::setUrl()
     */
    public function setUrl($url) {
        $this->url = $url;

        return $this;
    }

    /**
     * @see Imbo\Validate\SignatureInterface::setTimestamp()
     */
    public function setTimestamp($timestamp) {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * @see Imbo\Validate\SignatureInterface::setPublicKey()
     */
    public function setPublicKey($key) {
        $this->publicKey = $key;

        return $this;
    }

    /**
     * @see Imbo\Validate\SignatureInterface::setPrivateKey()
     */
    public function setPrivateKey($key) {
        $this->privateKey = $key;

        return $this;
    }

    /**
     * @see Imbo\Validate\ValidateInterface::isValid()
     */
    public function isValid($value) {
        // Generate data for the HMAC
        $data = $this->httpMethod . '|' . $this->url . '|' . $this->publicKey . '|' . $this->timestamp;

        // Compare
        return ($value === hash_hmac($this->algo, $data, $this->privateKey));
    }
}
