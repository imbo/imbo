<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Http\Response\Formatter;

use Imbo\Http\Response\Formatter\JSON;
use Imbo\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Http\Response\Formatter\Formatter
 * @group unit
 * @group http
 * @group formatters
 */
class FormatterTest extends TestCase {
    /**
     * @covers Imbo\Http\Response\Formatter\Formatter::format
     */
    public function testThrowsExceptionWhenModelIsNotSupported() {
        $formatter = new JSON($this->createMock('Imbo\Helpers\DateFormatter'));
        $this->expectExceptionObject(new InvalidArgumentException('Unsupported model type', 500));
        $formatter->format($this->createMock('Imbo\Model\ModelInterface'));
    }
}
