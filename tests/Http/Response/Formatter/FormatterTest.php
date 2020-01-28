<?php declare(strict_types=1);
namespace Imbo\Http\Response\Formatter;

use Imbo\Exception\InvalidArgumentException;
use Imbo\Helpers\DateFormatter;
use Imbo\Model\ModelInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Http\Response\Formatter\Formatter
 */
class FormatterTest extends TestCase {
    /**
     * @covers ::format
     */
    public function testThrowsExceptionWhenModelIsNotSupported() : void {
        $formatter = new JSON($this->createMock(DateFormatter::class));
        $this->expectExceptionObject(new InvalidArgumentException('Unsupported model type', 500));
        $formatter->format($this->createMock(ModelInterface::class));
    }
}
