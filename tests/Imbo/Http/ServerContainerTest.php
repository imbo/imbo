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

namespace Imbo\Http;

/**
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Http\ServerContainer
 */
class ServerContainerTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\Http\ServerContainer::__construct
     * @covers Imbo\Http\ServerContainer::getHeaders
     */
    public function testGetHeaders() {
        $parameters = array(
            'key' => 'value',
            'otherKey' => 'otherValue',
            'content-length' => 123,
            'CONTENT_LENGTH' => 234,
            'content-type' => 'text/html',
            'CONTENT_TYPE' => 'image/png',
            'HTTP_IF_NONE_MATCH' => 'asdf',
        );

        $container = new ServerContainer($parameters);
        $headers = $container->getHeaders();
        $this->assertSame(3, count($headers));
        $this->assertSame(234, $headers['CONTENT_LENGTH']);
        $this->assertSame('image/png', $headers['CONTENT_TYPE']);
        $this->assertSame('asdf', $headers['IF_NONE_MATCH']);
    }
}
