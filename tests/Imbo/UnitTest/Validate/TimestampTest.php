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

namespace Imbo\UnitTest\Validate;

use Imbo\Validate\Timestamp;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Validate\Timestamp
 */
class TimestampTest extends \PHPUnit_Framework_TestCase {
    private $validate;

    public function setUp() {
        $this->validate = new Timestamp();
    }

    public function tearDown() {
        $this->timestamp = null;
    }

    public function getValidationData() {
        return array(
            array(0, true),
            array(100, true),
            array(-100, true),
            array(130, false),
            array(-130, false),
        );
    }

    /**
     * @dataProvider getValidationData()
     * @covers Imbo\Validate\Timestamp::isValid
     */
    public function testIsValid($offset, $result) {
        $date = gmdate('Y-m-d\TH:i:s\Z', time() + $offset);
        $this->assertSame($result, $this->validate->isValid($date));
    }

    /**
     * @covers Imbo\Validate\Timestamp::isValid
     */
    public function testIsValidWithInvalidFormat() {
        $this->assertFalse($this->validate->isValid(time()));
    }
}
