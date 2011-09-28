<?php
/**
 * Imbo
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
 * @package Imbo
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Http\Response;

use Imbo\Http\Request\RequestInterface;
use Imbo\Http\Response\Formatter;

/**
 * Response writer
 *
 * @package Imbo
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class ResponseWriter implements ResponseWriterInterface {
    /**
     * Request instance
     *
     * The request instance is used to dynamically select a formatter to write the response to the
     * client.
     *
     * @var Imbo\Http\Request\RequestInterface
     */
    private $request;

    /**
     * Class constructor
     *
     * @param Imbo\Http\Request\RequestInterface $request The request instance
     */
    public function __construct(RequestInterface $request) {
        $this->request = $request;
    }

    /**
     * @see Imbo\Http\Response\ResponseWriterInterface::write()
     * @TODO Check the Accept headers to make sure we have a valid formatter. Also check
     *       Accept-Charset to possibly convert.
     */
    public function write(array $data) {
        $formatter = new Formatter\Json();
        return $formatter->format($data);
    }

    /**
     * @see Imbo\Http\Response\ResponseWriterInterface::getContentType()
     * @TODO Use the Accept-Charset header to inject the used charset in the content-type returned
     *       from the formatter.
     */
    public function getContentType() {
        $formatter = new Formatter\Json();
        return $formatter->getContentType();
    }
}
