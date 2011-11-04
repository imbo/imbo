<?php
/**
 * Imbo
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
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
 * @package Imbo
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\EventManager\Listener;

use Imbo\Image\Transformation;

/**
 * @package Imbo
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class ResizeConstraintTest extends ListenerTests {
    private $maxWidth = 123;
    private $maxHeight = 234;

    protected function getListener() {
        return new ResizeConstraint($this->maxWidth, $this->maxHeight);
    }

    /**
     * @expectedException Imbo\Exception
     * @expectedExceptionCode 400
     */
    public function testInvokeWithIllegalResizeParameters() {
        $listener = $this->getListener();

        $transformations = array(
            new Transformation\Resize(1001),
        );

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getTransformations')->will($this->returnValue($transformations));

        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $listener($event);
    }

    public function testInvoke() {
        $listener = $this->getListener();

        $transformations = array(
            new Transformation\Resize(100, 200),
        );

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getTransformations')->will($this->returnValue($transformations));

        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $listener($event);
    }
}
