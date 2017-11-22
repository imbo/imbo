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
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\EventListener\ImageTransformationLimiter
 * @group integration
 * @group listeners
 */
class ImageTransformationLimiterTest extends TestCase {
    /**
     * @covers Imbo\EventListener\ImageTransformationLimiter::__construct
     * @covers Imbo\EventListener\ImageTransformationLimiter::checkTransformationCount
     * @covers Imbo\EventListener\ImageTransformationLimiter::setTransformationLimit
     */
    public function testLimitsTransformationCount() {
        $limit = 2;
        $listener = new ImageTransformationLimiter(['limit' => $limit]);

        $request = $this->createMock('Imbo\Http\Request\Request');

        // content of array isn't important, the check is done on the count of the array
        $request->expects($this->any())->method('getTransformations')->will($this->returnValue([1, 2, 3, 4, 5]));

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($request));

        $this->expectExceptionObject(
            new ResourceException(
                sprintf(
                    'Too many transformations applied to resource. The limit is %d transformations.',
                    $limit
                ),
                403
            )
        );

        $listener->checkTransformationCount($event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationLimiter::__construct
     * @covers Imbo\EventListener\ImageTransformationLimiter::checkTransformationCount
     * @covers Imbo\EventListener\ImageTransformationLimiter::setTransformationLimit
     */
    public function testAllowsTransformationCount() {
        $listener = new ImageTransformationLimiter(['limit' => 2]);

        $request = $this->createMock('Imbo\Http\Request\Request');

        // content of array isn't important, the check is done on the count of the array
        $request->expects($this->any())->method('getTransformations')->will($this->returnValue([1, 2]));

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($request));

        $listener->checkTransformationCount($event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationLimiter::__construct
     * @covers Imbo\EventListener\ImageTransformationLimiter::checkTransformationCount
     * @covers Imbo\EventListener\ImageTransformationLimiter::setTransformationLimit
     */
    public function testAllowsAnyTransformationCount() {
        $listener = new ImageTransformationLimiter(['limit' => 0]);

        $request = $this->createMock('Imbo\Http\Request\Request');

        // content of array isn't important, the check is done on the count of the array
        $request->expects($this->any())->method('getTransformations')->will($this->returnValue([1, 2, 3, 4, 5, 6, 7, 8, 9]));

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($request));

        $listener->checkTransformationCount($event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformationLimiter::__construct
     * @covers Imbo\EventListener\ImageTransformationLimiter::getTransformationLimit
     * @covers Imbo\EventListener\ImageTransformationLimiter::setTransformationLimit
     */
    public function testGetSetLimitCountTransformationCount() {
        $listener = new ImageTransformationLimiter(['limit' => 42]);
        $this->assertSame(42, $listener->getTransformationLimit());

        $listener->setTransformationLimit(10);
        $this->assertSame(10, $listener->getTransformationLimit());
    }
}
