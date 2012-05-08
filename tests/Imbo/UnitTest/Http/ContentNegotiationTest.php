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

namespace Imbo\UnitTest\Http;

use Imbo\Http\ContentNegotiation;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Http\ContentNegotiation
 */
class ContentNegotiationTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\Http\ContentNegotiation
     */
    private $cn;

    /**
     * Set up
     */
    public function setUp() {
        $this->cn = new ContentNegotiation();
    }

    /**
     * Tear down
     */
    public function tearDown() {
        $this->cn = null;
    }

    /**
     * @return array[]
     */
    public function getIsAcceptableData() {
        return array(
            array('image/png', array('image/png' => 1, 'image/*' => 0.9), 1),
            array('image/png', array('text/html' => 1, '*/*' => 0.9), 0.9),
            array('image/png', array('text/html' => 1), false),
            array('image/jpeg', array('application/json' => 1, 'text/*' => 0.9), false),
            array('application/json', array('text/html;level=1' => 1, 'text/html' => 0.9, '*/*' => 0.8, 'text/html;level=2' => 0.7, 'text/*' => 0.9), 0.8),
        );
    }

    /**
     * @dataProvider getIsAcceptableData
     * @covers Imbo\Http\ContentNegotiation::isAcceptable
     */
    public function testIsAcceptable($mimeType, $acceptable, $result) {
        $this->assertSame($result, $this->cn->isAcceptable($mimeType, $acceptable));
    }
}
