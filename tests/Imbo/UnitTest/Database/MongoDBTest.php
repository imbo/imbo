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

namespace Imbo\UnitTest\Database;

use Imbo\Database\MongoDB;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Database\MongoDB
 */
class GridFSTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\Database\MongoDB
     */
    private $driver;

    /**
     * @var Mongo
     */
    private $mongo;

    /**
     * @var MongoCollection
     */
    private $collection;

    /**
     * Setup method
     */
    public function setUp() {
        if (!extension_loaded('mongo')) {
            $this->markTestSkipped('pecl/mongo is required to run this test');
        }

        $this->mongo = $this->getMockBuilder('Mongo')->disableOriginalConstructor()->getMock();
        $this->collection = $this->getMockBuilder('MongoCollection')->disableOriginalConstructor()->getMock();
        $this->driver = new MongoDB(array(), $this->mongo, $this->collection);
    }

    /**
     * Teardown method
     */
    public function tearDown() {
        $this->mongo = null;
        $this->collection = null;
        $this->driver = null;
    }

    /**
     * @covers Imbo\Database\MongoDB::getStatus
     */
    public function testGetStatusWhenMongoIsNotConnectable() {
        $this->mongo->expects($this->once())->method('connect')->will($this->returnValue(false));
        $this->assertFalse($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Database\MongoDB::getStatus
     */
    public function testGetStatusWhenMongoIsConnectable() {
        $this->mongo->expects($this->once())->method('connect')->will($this->returnValue(true));
        $this->assertTrue($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Database\MongoDB::getStatus
     */
    public function testDottedNotationForMetadataQuery() {
        $publicKey = 'key';

        $query = $this->getMock('Imbo\Resource\Images\QueryInterface');
        $query->expects($this->once())->method('from')->will($this->returnValue(null));
        $query->expects($this->once())->method('to')->will($this->returnValue(null));
        $query->expects($this->once())->method('metadataQuery')->will($this->returnValue(array(
            'style' => 'IPA',
            'brewery' => 'Nøgne Ø',
        )));
        $query->expects($this->any())->method('limit')->will($this->returnValue(10));

        $cursor = $this->getMockBuilder('MongoCursor')->disableOriginalConstructor()->getMock();
        $cursor->expects($this->once())->method('limit')->with(10)->will($this->returnSelf());
        $cursor->expects($this->once())->method('sort')->will($this->returnSelf());

        $this->collection->expects($this->once())->method('find')->with(array(
            'publicKey' => $publicKey,
            'metadata.style' => 'IPA',
            'metadata.brewery' => 'Nøgne Ø',
        ), $this->isType('array'))->will($this->returnValue($cursor));

        $this->assertSame(array(), $this->driver->getImages($publicKey, $query));
    }
}
