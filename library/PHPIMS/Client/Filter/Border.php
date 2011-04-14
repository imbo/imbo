<?php
/**
 * PHPIMS
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
 * @package PHPIMS
 * @subpackage Client
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Client\Filter;

use PHPIMS\Client\FilterInterface;

/**
 * Border filter
 *
 * @package PHPIMS
 * @subpackage Client
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class Border implements FilterInterface {
    /**
     * Width of the border
     *
     * @var int
     */
    private $width = null;

    /**
     * Height of the border
     *
     * @var int
     */
    private $height = null;

    /**
     * Color of the border
     *
     * @var string
     */
    private $color = null;

    /**
     * Class constructor
     *
     * @param string $color The color to set
     * @param int $width Width of the border
     * @param int $height Height of the border
     */
    public function __construct($color = null, $width = null, $height = null) {
        $this->color  = $color;
        $this->width  = $width;
        $this->height = $height;
    }

    /**
     * @see PHPIMS\Client\FilterInterface::getFilter()
     */
    public function getFilter() {
        $filter = 't[]=border';
        $params = array();

        if ($this->color !== null) {
            $params[] = 'color=' . $this->color;
        }

        if ($this->width !== null) {
            $params[] = 'width=' . $this->width;
        }

        if ($this->color !== null) {
            $params[] = 'height=' . $this->height;
        }

        if (!empty($params)) {
            $filter .= ':' . implode(',', $params);
        }

        return $filter;
    }
}