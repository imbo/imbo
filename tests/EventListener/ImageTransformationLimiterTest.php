<?php declare(strict_types=1);

namespace Imbo\EventListener;

use Imbo\EventManager\Event;
use Imbo\Exception\ResourceException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversClass(ImageTransformationLimiter::class)]
class ImageTransformationLimiterTest extends TestCase
{
    #[DataProvider('getLimitAndTransformations')]
    public function testLimitsTransformationCount(array $transformations, int $limit, ?string $exceptionMessage): void
    {
        $listener = new ImageTransformationLimiter(['limit' => $limit]);

        $request = $this->createConfiguredStub(Request::class, [
            'getTransformations' => $transformations,
        ]);

        $event = $this->createConfiguredStub(Event::class, [
            'getRequest' => $request,
        ]);

        if ($exceptionMessage) {
            $this->expectExceptionObject(new ResourceException(
                $exceptionMessage,
                Response::HTTP_FORBIDDEN,
            ));
        } else {
            $this->expectNotToPerformAssertions();
        }

        $listener->checkTransformationCount($event);
    }

    #[DataProvider('getLimits')]
    public function testGetSetLimitCountTransformationCount(int $limit): void
    {
        $this->assertSame(
            $limit,
            $actual = (new ImageTransformationLimiter(['limit' => $limit]))->getTransformationLimit(),
            sprintf('Expected limit to be %d, got: %d', $limit, $actual),
        );
    }

    /**
     * @return array<array{transformations:array<int>,limit:int,exceptionMessage:?string}>
     */
    public static function getLimitAndTransformations(): array
    {
        return [
            [
                'transformations' => [1, 2, 3, 4, 5],
                'limit' => 2,
                'exceptionMessage' => 'Too many transformations applied to resource. The limit is 2 transformations.',
            ],
            [
                'transformations' => [1, 2],
                'limit' => 2,
                'exceptionMessage' => null,
            ],
            [
                'transformations' => [1, 2, 3, 4, 5, 6, 7, 8, 9],
                'limit' => 0,
                'exceptionMessage' => null,
            ],
        ];
    }

    /**
     * @return array<array{limit:int}>
     */
    public static function getLimits(): array
    {
        return [
            ['limit' => 42],
            ['limit' => 10],
        ];
    }
}
