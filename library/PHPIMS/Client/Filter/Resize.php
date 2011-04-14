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
use PHPIMS\Client\Filter\Exception as FilterException;

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
class Resize implements FilterInterface {
    /**
     * Width of the resize
     *
     * @var int
     */
    private $width = null;

    /**
     * Height of the resize
     *
     * @var int
     */
    private $height = null;

    /**
     * Class constructor
     *
     * @param int $width Width of the resize
     * @param int $height Height of the resize
     * @throws PHPIMS\Client\Filter\Exception
     */
    public function __construct($width = null, $height = null) {
        if ($width === null && $height === null) {
            throw new FilterException('$width and/or $height must be set');
        }

        $this->width  = $width;
        $this->height = $height;
    }

    /**
     * @see PHPIMS\Client\FilterInterface::getFilter()
     */
    public function getFilter() {
        $filter = 't[]=resize';
        $params = array();

        if ($this->width !== null) {
            $params[] = 'width=' . $this->width;
        }

        if ($this->height !== null) {
            $params[] = 'height=' . $this->height;
        }

        return $filter . ':' . implode(',', $params);
    }
}