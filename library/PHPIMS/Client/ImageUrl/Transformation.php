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

namespace PHPIMS\Client\ImageUrl;

use PHPIMS\Client;

use PHPIMS\Client\ImageUrl;
use PHPIMS\Client\ImageUrl\Filter\Border;
use PHPIMS\Client\ImageUrl\Filter\Crop;
use PHPIMS\Client\ImageUrl\Filter\Resize;
use PHPIMS\Client\ImageUrl\Filter\Rotate;

/**
 * Transformation collection
 *
 * @package PHPIMS
 * @subpackage Client
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class Transformation {
    /**
     * Filters added
     *
     * @var array
     */
    private $filters = array();

    /**
     * Add a filter to the chain
     *
     * @param FilterInterface $filter The filter to add
     * @return PHPIMS\Client\ImageUrl\Transformation
     */
    public function add(FilterInterface $filter) {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Apply a filter to an url
     *
     * @param PHPIMS\Client\ImageUrl ImageUrl $url
     * @param PHPIMS\Client\ImageUrl\FilterInterface $filter
     * @return PHPIMS\Client\ImageUrl
     */
    public function applyFilter(ImageUrl $url, FilterInterface $filter) {
        return $url->append($filter->getFilter());
    }

    /**
     * Apply the filter chain to an url
     *
     * @param PHPIMS\Client\ImageUrl $imageUrl The url to apply filters to
     * @return PHPIMS\Client\ImageUrl
     */
    public function apply(ImageUrl $url) {
        if (count($this->filters)) {
            return array_reduce($this->filters, array($this, 'applyFilter'), $url);
        }

        return $url;
    }

    /**
     * Border filter
     *
     * @param string $color The color to use
     * @param int $width Width of the border
     * @param int $height Height of the border
     * @return PHPIMS\Client\ImageUrl\Transformation
     */
    public function border($color = null, $width = null, $height = null) {
        return $this->add(new Border($color, $width, $height));
    }

    /**
     * Crop filter
     *
     * @param int $x X coordinate of the top left corner of the crop
     * @param int $y Y coordinate of the top left corner of the crop
     * @param int $width Width of the crop
     * @param int $height Height of the crop
     * @return PHPIMS\Client\ImageUrl\Transformation
     */
    public function crop($x, $y, $width, $height) {
        return $this->add(new Crop($x, $y, $width, $height));
    }

    /**
     * Rotate filter
     *
     * @param int $angle Angle of the rotation
     * @param string $bg Background color
     * @return PHPIMS\Client\ImageUrl\Transformation
     */
    public function rotate($angle, $bg = null) {
        return $this->add(new Rotate($angle, $bg));
    }

    /**
     * Resize filter
     *
     * @param int $width Width of the resize
     * @param int $height Height of the resize
     * @return PHPIMS\Client\ImageUrl\Transformation
     */
    public function resize($width = null, $height = null) {
        return $this->add(new Resize($width, $height));
    }
}