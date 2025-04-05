<?php declare(strict_types=1);
namespace Imbo\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RuntimeException::class)]
class RuntimeExceptionTest extends TestCase
{
    #[DataProvider('getErrorCodes')]
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
