<?php declare(strict_types=1);
namespace Imbo\Exception;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Exception\InvalidArgumentException
 */
class InvalidArgumentExceptionTest extends TestCase
{
    /**
     * @return array<int,array{0:int,1:int}>
     */
    public static function getErrorCodes(): array
    {
        return [
            [123, 123],
            [0, 0],
        ];
    }

    /**
     * @dataProvider getErrorCodes
     * @covers ::getImboErrorCode
     * @covers ::setImboErrorCode
     */
    public function testSetAndGetImboErrorCode(int $actual, int $expected): void
    {
        $exception = new InvalidArgumentException();
        $this->assertSame($exception, $exception->setImboErrorCode($actual));
        $this->assertSame($expected, $exception->getImboErrorCode());
    }
}
