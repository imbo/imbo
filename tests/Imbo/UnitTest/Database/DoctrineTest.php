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

use Imbo\Database\Doctrine,
    Doctrine\DBAL\Connection;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Database\Doctrine
 */
class DoctrineTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Doctrine
     */
    private $driver;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Set up the driver
     */
    public function setUp() {
        if (!class_exists('Doctrine\DBAL\Connection')) {
            $this->markTestSkipped('Doctrine is required to run this test');
        }

        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $this->driver = new Doctrine(array(), $this->connection);
    }

    /**
     * Tear down the driver
     */
    public function tearDown() {
        $this->connection = null;
        $this->driver = null;
    }

    /**
     * @covers Imbo\Database\Doctrine::getStatus
     */
    public function testGetStatusWhenDatabaseIsAlreadyConnected() {
        $this->connection->expects($this->once())->method('isConnected')->will($this->returnValue(true));
        $this->assertTrue($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Database\Doctrine::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndCanConnect() {
        $this->connection->expects($this->once())->method('isConnected')->will($this->returnValue(false));
        $this->connection->expects($this->once())->method('connect')->will($this->returnValue(true));
        $this->assertTrue($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Database\Doctrine::getStatus
     */
    public function testGetStatusWhenDatabaseIsNotConnectedAndCanNotConnect() {
        $this->connection->expects($this->once())->method('isConnected')->will($this->returnValue(false));
        $this->connection->expects($this->once())->method('connect')->will($this->returnValue(false));
        $this->assertFalse($this->driver->getStatus());
    }
}
