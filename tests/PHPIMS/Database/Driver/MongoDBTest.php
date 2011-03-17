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
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_Database_Driver_MongoDBTest extends PHPUnit_Framework_TestCase {
    /**
     * Driver instance
     *
     * @var PHPIMS_Database_Driver_MongoDB
     */
    protected $driver = null;

    /**
     * Set up method
     */
    public function setUp() {
        $this->driver = new PHPIMS_Database_Driver_MongoDB();
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->driver = null;
    }

    public function testStaticIsValidHash() {
        $id = new MongoId();
        $this->assertTrue(PHPIMS_Database_Driver_MongoDB::isValidHash((string) $id));

        $id = 123;
        $this->assertFalse(PHPIMS_Database_Driver_MongoDB::isValidHash($id));
    }

    public function testSetGetDatabaseName() {
        $name = 'someName';
        $this->driver->setDatabaseName($name);
        $this->assertSame($name, $this->driver->getDatabaseName());
    }

    public function testSetGetCollectionName() {
        $name = 'someName';
        $this->driver->setCollectionName($name);
        $this->assertSame($name, $this->driver->getCollectionName());
    }

    public function testSetGetDatabase() {
        $mongo = $this->getMockBuilder('MongoDB')->disableOriginalConstructor()->getMock();
        $this->driver->setDatabase($mongo);
        $this->assertSame($mongo, $this->driver->getDatabase());
    }

    public function testSetGetCollection() {
        $collection = $this->getMockBuilder('MongoCollection')->disableOriginalConstructor()->getMock();
        $this->driver->setCollection($collection);
        $this->assertSame($collection, $this->driver->getCollection());
    }
}