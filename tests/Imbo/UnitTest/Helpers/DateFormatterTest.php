<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest;

use Imbo\Helpers\DateFormatter,
    DateTime,
    DateTimeZone;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
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
        return array(
            array(new DateTime('@1234567890'), 'Fri, 13 Feb 2009 23:31:30 GMT'),
            array(new DateTime('16/Mar/2012:15:05:00 +0100'), 'Fri, 16 Mar 2012 14:05:00 GMT'),
        );
    }

    /**
     * @dataProvider getDates
     * @covers Imbo\Helpers\DateFormatter::formatDate
     */
    public function testCanFormatADateTimeInstance($datetime, $expected) {
        $this->assertSame($expected, $this->helper->formatDate($datetime));
    }
}
