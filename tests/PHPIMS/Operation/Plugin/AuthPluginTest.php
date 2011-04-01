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

use \Mockery as m;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_Operation_Plugin_AuthPluginTest extends PHPUnit_Framework_TestCase {
    /**
     * Plugin instance
     *
     * @var PHPIMS_Operation_Plugin_AuthPlugin
     */
    protected $plugin = null;

    public function setUp() {
        $this->plugin = new PHPIMS_Operation_Plugin_AuthPlugin();
    }

    public function tearDown() {
        $this->plugin = null;
    }

    /**
     * @expectedException PHPIMS_Operation_Plugin_Exception
     * @expectedExceptionCode 400
     */
    public function testExecWithMissingParameters() {
        $operation = m::mock('PHPIMS_Operation_Abstract');
        $this->plugin->exec($operation);
    }

    /**
     * @expectedException PHPIMS_Operation_Plugin_Exception
     * @expectedExceptionCode 401
     * @expectedExceptionMessage Timestamp expired
     */
    public function testExecWithTimestampTooFarInTheFuture() {
        $_GET['signature'] = 'some signature';
        $_GET['publicKey'] = 'some key';
        // Set timestamp 5 minutes and 5 seconds in the future
        $_GET['timestamp'] = gmdate('Y-m-d\TH:i\Z', time() + 305);

        $operation = m::mock('PHPIMS_Operation_Abstract');

        $this->plugin->exec($operation);
    }

    /**
     * @expectedException PHPIMS_Operation_Plugin_Exception
     * @expectedExceptionCode 401
     * @expectedExceptionMessage Timestamp expired
     */
    public function testExecWithTimestampTooOld() {
        $_GET['signature'] = 'some signature';
        $_GET['publicKey'] = 'some key';
        // Set timestamp 5 minutes and 5 seconds in the past
        $_GET['timestamp'] = gmdate('Y-m-d\TH:i\Z', time() - 305);

        $operation = m::mock('PHPIMS_Operation_Abstract');

        $this->plugin->exec($operation);
    }
}