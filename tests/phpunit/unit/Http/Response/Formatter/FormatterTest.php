<?php
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
