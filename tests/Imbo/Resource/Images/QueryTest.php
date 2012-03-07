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

namespace Imbo\Resource\Images;

/**
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class QueryTest extends \PHPUnit_Framework_TestCase {
    /**
     * Query instance
     *
     * @var Imbo\Resource\Images\Query
     */
    private $query;

    public function setUp() {
        $this->query = new Query();
    }

    public function tearDown() {
        $this->query = null;
    }

    public function testPage() {
        $value = 2;
        $this->assertSame(1, $this->query->page());
        $this->query->page($value);
        $this->assertSame($value, $this->query->page());
    }

    public function testLimit() {
        $value = 30;
        $this->assertSame(20, $this->query->limit());
        $this->query->limit($value);
        $this->assertSame($value, $this->query->limit());
    }

    public function testReturnMetadata() {
        $this->assertFalse($this->query->returnMetadata());
        $this->query->returnMetadata(true);
        $this->assertTrue($this->query->returnMetadata());
    }

    public function testMetadataQuery() {
        $value = array('category' => 'some category');
        $this->assertSame(array(), $this->query->metadataQuery());
        $this->query->metadataQuery($value);
        $this->assertSame($value, $this->query->metadataQuery());
    }

    public function testFrom() {
        $value = 123123123;
        $this->assertNull($this->query->from());
        $this->query->from($value);
        $this->assertSame($value, $this->query->from());
    }

    public function testTo() {
        $value = 123123123;
        $this->assertNull($this->query->to());
        $this->query->to($value);
        $this->assertSame($value, $this->query->to());
    }
}
