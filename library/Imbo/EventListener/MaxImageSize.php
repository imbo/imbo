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
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\Image\Transformation\MaxSize;

/**
 * Max image size event listener
 *
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class MaxImageSize extends Listener implements ListenerInterface {
    /**
     * Max width
     *
     * @var int
     */
    private $width;

    /**
     * Max height
     *
     * @var int
     */
    private $height;

    /**
     * Class constructor
     *
     * @param int $width Max width
     * @param int $height Max height
     */
    public function __construct($width = null, $height = null) {
        $this->width  = (int) $width;
        $this->height = (int) $height;
    }

    /**
     * {@inheritdoc}
     */
    public function getEvents() {
        return array(
            'image.put.imagepreparation.post',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function invoke(EventInterface $event) {
        $container = $event->getContainer();

        $image  = $container->get('image');

        $width  = $image->getWidth();
        $height = $image->getHeight();

        if (($this->width && ($width > $this->width)) || ($this->height && ($height > $this->height))) {
            $transformation = new MaxSize($this->width, $this->height);
            $transformation->applyToImage($image);

            // Update raw data in request to reflect the new image
            $container->get('request')->setRawData($image->getBlob());
        }
    }
}
