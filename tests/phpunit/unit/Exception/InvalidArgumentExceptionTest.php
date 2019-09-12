<?php
namespace ImboUnitTest\Exception;

use Imbo\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Exception\InvalidArgumentException
 */
class InvalidArgumentExceptionTest extends TestCase {
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
     * @covers Imbo\Exception\InvalidArgumentException::setImboErrorCode
     * @covers Imbo\Exception\InvalidArgumentException::getImboErrorCode
     * @dataProvider getErrorCodes
     */
    public function testSetAndGetImboErrorCode($actual, $expected) {
        $exception = new InvalidArgumentException();
        $this->assertSame($exception, $exception->setImboErrorCode($actual));
        $this->assertSame($expected, $exception->getImboErrorCode());
    }

}
