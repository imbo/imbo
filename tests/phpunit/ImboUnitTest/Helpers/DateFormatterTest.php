<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest;

use Imbo\Helpers\DateFormatter,
    DateTime,
    DateTimeZone;

/**
 * @covers Imbo\Helpers\DateFormatter
 * @group unit
 * @group helpers
 */
class DateFormatterTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var DateFormatter
     */
    private $helper;

    /**
     * Set up the helper
     */
    public function setUp() {
        $this->helper = new DateFormatter();
    }

    /**
     * Tear down the helper
     */
    public function tearDown() {
        $this->helper = null;
    }

    /**
     * Get different datetimes
     *
     * @return array[]
     */
    public function getDates() {
        return [
            [new DateTime('@1234567890'), 'Fri, 13 Feb 2009 23:31:30 GMT'],
            [new DateTime('16/Mar/2012:15:05:00 +0100'), 'Fri, 16 Mar 2012 14:05:00 GMT'],
        ];
    }

    /**
     * @dataProvider getDates
     * @covers Imbo\Helpers\DateFormatter::formatDate
     */
    public function testCanFormatADateTimeInstance($datetime, $expected) {
        $this->assertSame($expected, $this->helper->formatDate($datetime));
    }
}
