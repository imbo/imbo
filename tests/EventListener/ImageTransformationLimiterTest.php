<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\Event;
use Imbo\Exception\ResourceException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\EventListener\ImageTransformationLimiter
 */
class ImageTransformationLimiterTest extends TestCase
{
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
     * @dataProvider getLimitAndTransformations
     * @covers ::__construct
     * @covers ::checkTransformationCount
     * @covers ::setTransformationLimit
     */
    public function testLimitsTransformationCount(array $transformations, int $limit, ?string $exceptionMessage): void
    {
        $listener = new ImageTransformationLimiter(['limit' => $limit]);

        $request = $this->createConfiguredMock(Request::class, [
            'getTransformations' => $transformations,
        ]);

        $event = $this->createConfiguredMock(Event::class, [
            'getRequest' => $request,
        ]);

        if ($exceptionMessage) {
            $this->expectExceptionObject(new ResourceException(
                $exceptionMessage,
                Response::HTTP_FORBIDDEN,
            ));
        }

        $this->assertNull(
            $listener->checkTransformationCount($event),
            'Did not expect method to return anything',
        );
    }

    public static function getLimits(): array
    {
        return [
            [42],
            [10],
        ];
    }

    /**
     * @dataProvider getLimits
     * @covers ::__construct
     * @covers ::getTransformationLimit
     * @covers ::setTransformationLimit
     */
    public function testGetSetLimitCountTransformationCount(int $limit): void
    {
        $this->assertSame(
            $limit,
            $actual = (new ImageTransformationLimiter(['limit' => $limit]))->getTransformationLimit(),
            sprintf('Expected limit to be %d, got: %d', $limit, $actual),
        );
    }
}
