<?php
namespace ImboUnitTest\Http\Response\Formatter;

use Imbo\Http\Response\Formatter\JSON;
use Imbo\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Http\Response\Formatter\Formatter
 */
class FormatterTest extends TestCase {
    /**
     * @covers ::format
     */
    public function testThrowsExceptionWhenModelIsNotSupported() {
        $formatter = new JSON($this->createMock('Imbo\Helpers\DateFormatter'));
        $this->expectExceptionObject(new InvalidArgumentException('Unsupported model type', 500));
        $formatter->format($this->createMock('Imbo\Model\ModelInterface'));
    }
}
