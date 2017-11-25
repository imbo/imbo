<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\EventListener;

use Imbo\EventListener\ImageTransformationLimiter;
use Imbo\Exception\ResourceException;
use Imbo\Http\Request\Request;
use Imbo\EventManager\Event;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\EventListener\ImageTransformationLimiter
 * @coversDefaultClass Imbo\EventListener\ImageTransformationLimiter
 * @group integration
 * @group listeners
 */
class ImageTransformationLimiterTest extends TestCase {
    /**
     * Data provider
     *
     * @return array[]
     */
    public function getLimitAndTransformations() {
        return [
            [
                'transformations' => [1, 2, 3, 4, 5],
                'limit' => 2,
                'exceptionMessage' => 'Too many transformations applied to resource. The limit is 2 transformations.'
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
     *
     * @param array $transformations
     * @param int $limit
     */
    public function testLimitsTransformationCount(array $transformations, $limit, $exceptionMessage) {
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
                403
            ));
        }

        $this->assertNull(
            $listener->checkTransformationCount($event),
            'Did not expect method to return anything'
        );
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getLimits() {
        return [
            ['limit' => 42],
            ['limit' => 10],
        ];
    }

    /**
     * @dataProvider getLimits
     * @covers ::__construct
     * @covers ::getTransformationLimit
     * @covers ::setTransformationLimit
     *
     * @param int $limit The limit to set and get
     */
    public function testGetSetLimitCountTransformationCount($limit) {
        $this->assertSame(
            $limit,
            $actual = (new ImageTransformationLimiter(['limit' => $limit]))->getTransformationLimit(),
            sprintf('Expected limit to be %d, got: %d', $limit, $actual)
        );
    }
}
