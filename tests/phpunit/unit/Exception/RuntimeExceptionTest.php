<?php declare(strict_types=1);
namespace ImboUnitTest\Exception;

use Imbo\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Exception\RuntimeException
 */
class RuntimeExceptionTest extends TestCase {
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
    public function testSetAndGetImboErrorCode($actual, $expected) : void {
        $exception = new RuntimeException();
        $this->assertSame($exception, $exception->setImboErrorCode($actual));
        $this->assertSame($expected, $exception->getImboErrorCode());
    }

}
