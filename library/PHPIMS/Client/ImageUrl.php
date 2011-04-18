<?php
/**
 * PHPIMS
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
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
 * @package PHPIMS
 * @subpackage Client
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Client;

/**
 * URL to an image
 *
 * @package PHPIMS
 * @subpackage Client
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class ImageUrl {
    /**
     * Baseurl to the PHPIMS service
     *
     * @var string
     */
    private $baseUrl;

    /**
     * Query data
     *
     * @var array
     */
    private $data;

    /**
     * Class constructor
     *
     * @param string $baseUrl The url to an image
     */
    public function __construct($baseUrl) {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Append something to the url
     *
     * @param string $part The part to append
     * @return PHPIMS\Client\ImageUrl
     */
    public function append($part) {
        $this->data[] = $part;

        return $this;
    }

    /**
     * To string method
     *
     * @return string
     */
    public function __toString() {
        if (empty($this->data)) {
            return $this->baseUrl;
        }

        $query = null;
        $query = array_reduce($this->data, function($query, $element) { return $query . 't[]=' . $element . '&'; }, $query);
        $query = rtrim($query, '&');

        return $this->baseUrl . '?' . $query;
    }
}