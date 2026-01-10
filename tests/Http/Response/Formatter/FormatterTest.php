<?php declare(strict_types=1);

namespace Imbo\Http\Response\Formatter;

use Imbo\Exception\InvalidArgumentException;
use Imbo\Helpers\DateFormatter;
use Imbo\Http\Response\Response;
use Imbo\Model\ModelInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Formatter::class)]
class FormatterTest extends TestCase
{
    public function testThrowsExceptionWhenModelIsNotSupported(): void
    {
        $formatter = new JSON($this->createStub(DateFormatter::class));
        $this->expectExceptionObject(new InvalidArgumentException('Unsupported model type', Response::HTTP_INTERNAL_SERVER_ERROR));
        $formatter->format($this->createStub(ModelInterface::class));
    }
}
