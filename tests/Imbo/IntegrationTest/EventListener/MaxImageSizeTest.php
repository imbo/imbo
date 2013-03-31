<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\IntegrationTest\EventListener;

use Imbo\EventListener\MaxImageSize,
    Imbo\Model\Image;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 */
class MaxImageSizeTest extends \PHPUnit_Framework_TestCase {
    /**
     * Get dimensions for the test
     *
     * @return array[]
     */
    public function getDimensions() {
        return array(
            array(20, 10, 14, 10),
            array(100, 1000, 100, 70),
            array(1000, 1000, 665, 463),
        );
    }

    /**
     * @dataProvider getDimensions
     * @covers Imbo\EventListener\MaxImageSize::__construct
     * @covers Imbo\EventListener\MaxImageSize::invoke
     */
    public function testCanResizeImages($maxWidth, $maxHeight, $expectedWidth, $expectedHeight) {
        $listener = new MaxImageSize($maxWidth, $maxHeight);

        $image = new Image();
        $image->setBlob(file_get_contents(FIXTURES_DIR . '/image.png'))
              ->setWidth(665)
              ->setHeight(463);

        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $listener->invoke($event);

        $this->assertSame($expectedWidth, $image->getWidth());
        $this->assertSame($expectedHeight, $image->getHeight());
    }
}
