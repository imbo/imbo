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
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Image\Transformation;

use Mockery as m;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class BorderTest extends \PHPUnit_Framework_TestCase {
    public function testApplyToImage() {
        $image = m::mock('PHPIMS\Image');
        $image->shouldReceive('getBlob')->once()->andReturn(file_get_contents(__DIR__ . '/../../_files/image.png'));
        $image->shouldReceive('setBlob')->once()->with(m::type('string'))->andReturn($image);
        $image->shouldReceive('setWidth')->once()->with(665)->andReturn($image);
        $image->shouldReceive('setHeight')->once()->with(463)->andReturn($image);

        $transformation = new Border();
        $transformation->applyToImage($image);
    }

    public function testApplyToImageUrl() {
        $url = m::mock('PHPIMS\Client\ImageUrl');
        $url->shouldReceive('append')->with(m::on(function ($string) {
            return (preg_match('/^border:/', $string) && strstr($string, 'color=fed') &&
                    strstr($string, 'width=1') && strstr($string, 'height=2'));
        }))->once();
        $transformation = new Border('fed', 1, 2);
        $transformation->applyToImageUrl($url);
    }
}
