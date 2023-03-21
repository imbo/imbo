<?php declare(strict_types=1);
namespace Imbo\Helpers;

use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Helpers\DateFormatter
 */
class DateFormatterTest extends TestCase
{
    private DateFormatter $helper;

    public function setUp(): void
    {
        $this->helper = new DateFormatter();
    }

    /**
     * @return array<array{0:DateTime,1:string}>
     */
    public static function getDates(): array
    {
        return [
            [new DateTime('@1234567890'), 'Fri, 13 Feb 2009 23:31:30 GMT'],
            [new DateTime('16/Mar/2012:15:05:00 +0100'), 'Fri, 16 Mar 2012 14:05:00 GMT'],
        ];
    }

    /**
     * @dataProvider getDates
     * @covers ::formatDate
     */
    public function testCanFormatADateTimeInstance(DateTime $datetime, string $expected): void
    {
        $this->assertSame($expected, $this->helper->formatDate($datetime));
    }
}
