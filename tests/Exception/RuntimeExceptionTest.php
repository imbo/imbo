<?php declare(strict_types=1);
namespace Imbo\Exception;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Exception\RuntimeException
 */
class RuntimeExceptionTest extends TestCase
{
    /**
     * @dataProvider getErrorCodes
     * @covers ::setImboErrorCode
     * @covers ::getImboErrorCode
     */
    public function testSetAndGetImboErrorCode(int $actual, int $expected): void
    {
        $exception = new RuntimeException();
        $this->assertSame($exception, $exception->setImboErrorCode($actual));
        $this->assertSame($expected, $exception->getImboErrorCode());
    }

    /**
     * @return array<array{actual:int,expected:int}>
     */
    public static function getErrorCodes(): array
    {
        return [
            [
                'actual' => 123,
                'expected' => 123,
            ],
            [
                'actual' => 0,
                'expected' => 0,
            ],
        ];
    }
}
