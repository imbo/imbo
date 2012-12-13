<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\IntegrationTest\EventListener;

use Imbo\EventListener\MaxImageSize,
    Imbo\Image\Image;

/**
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\EventListener\MaxImageSize
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

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $listener->invoke($event);

        $this->assertSame($expectedWidth, $image->getWidth());
        $this->assertSame($expectedHeight, $image->getHeight());
    }
}
