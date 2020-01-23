<?php declare(strict_types=1);
namespace Imbo\Helpers;

use PHPUnit\Framework\TestCase;
use DateTime;

/**
 * @coversDefaultClass Imbo\Helpers\DateFormatter
 */
class DateFormatterTest extends TestCase {
    private $helper;

    public function setUp() : void {
        $this->helper = new DateFormatter();
    }

    public function getDates() : array {
        return [
            [new DateTime('@1234567890'), 'Fri, 13 Feb 2009 23:31:30 GMT'],
            [new DateTime('16/Mar/2012:15:05:00 +0100'), 'Fri, 16 Mar 2012 14:05:00 GMT'],
        ];
    }

    /**
     * @dataProvider getDates
     * @covers ::formatDate
     */
    public function testCanFormatADateTimeInstance($datetime, $expected) : void {
        $this->assertSame($expected, $this->helper->formatDate($datetime));
    }
}
