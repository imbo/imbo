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
class PHPIMS_Storage_Driver_AbstractTest extends PHPUnit_Framework_TestCase {
    /**
     * Driver instance
     *
     * @var PHPIMS_Storage_Driver_Abstract
     */
    protected $driver = null;

    /**
     * Set up method
     */
    public function setUp() {
        $this->driver = $this->getMockForAbstractClass('PHPIMS_Storage_Driver_Abstract');
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->driver = null;
    }

    public function testSetGetParams() {
        $params = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $this->driver->setParams($params);
        $this->assertSame($params, $this->driver->getParams());
    }

    public function testSetGetOperation() {
        $operation = $this->getMockBuilder('PHPIMS_Operation_Abstract')->disableOriginalConstructor()->getMockForAbstractClass();
        $this->driver->setOperation($operation);
        $this->assertSame($operation, $this->driver->getOperation());
    }
}