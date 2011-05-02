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

namespace PHPIMS\Operation;

use \Mockery as m;
use PHPIMS\Database\DriverInterface as DatabaseDriver;
use PHPIMS\Storage\DriverInterface as StorageDriver;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
abstract class OperationTests extends \PHPUnit_Framework_TestCase {
    /**
     * Database driver
     *
     * @var PHPIMS\Datbase\DriverInterface
     */
    protected $database = null;

    /**
     * Storage driver
     *
     * @var PHPIMS\Storage\DriverInterface
     */
    protected $storage = null;

    /**
     * Operation instance
     *
     * @var PHPIMS\Operation
     */
    protected $operation = null;

    /**
     * Image identifier used for the operation
     *
     * @var string
     */
    protected $imageIdentifier = null;

    /**
     * Set up method
     */
    public function setUp() {
        $this->database = m::mock('PHPIMS\\Database\\DriverInterface');
        $this->storage = m::mock('PHPIMS\\Storage\\DriverInterface');
        $this->imageIdentifier = md5(microtime()) . '.png';

        $this->operation = $this->getNewOperation();
        $this->operation->setImageIdentifier($this->imageIdentifier);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->operation = null;
    }

    /**
     * Get a new operation instance
     *
     * @return PHPIMS\Operation
     */
    abstract protected function getNewOperation();

    /**
     * Get the expected operation name for the operation class
     *
     * @return string
     */
    abstract protected function getExpectedOperationName();

    public function testGetOperationName() {
        $reflection = new \ReflectionClass($this->operation);
        $method = $reflection->getMethod('getOperationName');
        $method->setAccessible(true);

        $this->assertSame($this->getExpectedOperationName(), $method->invoke($this->operation));
    }
}