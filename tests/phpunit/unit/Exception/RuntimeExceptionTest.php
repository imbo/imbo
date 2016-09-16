<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Exception;

use Imbo\Exception\RuntimeException;

/**
 * @covers Imbo\Exception\RuntimeException
 * @group unit
 * @group exceptions
 */
class RuntimeExceptionTest extends \PHPUnit_Framework_TestCase {
    /**
     * Fetch imbo error codes
     *
     * @return array[]
     */
    public function getErrorCodes() {
        return [
            [123, 123],
            ['123', 123],
            [0, 0],
        ];
    }

    /**
     * @covers Imbo\Exception\RuntimeException::setImboErrorCode
     * @covers Imbo\Exception\RuntimeException::getImboErrorCode
     * @dataProvider getErrorCodes
     */
    public function testSetAndGetImboErrorCode($actual, $expected) {
        $exception = new RuntimeException();
        $this->assertSame($exception, $exception->setImboErrorCode($actual));
        $this->assertSame($expected, $exception->getImboErrorCode());
    }

}
