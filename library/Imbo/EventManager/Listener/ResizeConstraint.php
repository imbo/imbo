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
 * @package EventManager
 * @subpackage Listeners
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
namespace Imbo\EventManager\Listener;

use Imbo\EventManager\EventInterface,
    Imbo\Image\Transformation,
    Imbo\Exception;

/**
 * Resize constraint
 *
 * @package EventManager
 * @subpackage Listeners
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class ResizeConstraint implements ListenerInterface {
    /**
     * Max width of any resize transformation
     *
     * @var int
     */
    private $maxWidth;

    /**
     * Max height of any resize transformation
     *
     * @var int
     */
    private $maxHeight;


    /**
     * Class constructor
     *
     * @param int $maxWidth Max width of any resize transformation
     * @param int $maxHeight Max height of any resize transformation
     */
    public function __construct($maxWidth, $maxHeight) {
        $this->maxWidth  = (int) $maxWidth;
        $this->maxHeight = (int) $maxHeight;
    }

    /**
     * @see Imbo\EventManager\Listener\ListenerInterface::getEvents()
     */
    public function getEvents() {
        return array(
            'image.get.pre',
            'image.head.pre',
        );
    }

    /**
     * @see Imbo\EventManager\Listener\ListenerInterface::invoke()
     */
    public function invoke(EventInterface $event) {
        $request = $event->getRequest();
        $transformations = $request->getTransformations();

        foreach ($transformations as $t) {
            if ($t instanceof Transformation\Resize) {
                if ($t->width > $this->maxWidth || $t->height > $this->maxHeight) {
                    throw new Exception('Unsupported resize parameters', 400);
                }
            }
        }
    }
}
