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

use \Mockery as m;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class ThumbnailTest extends \PHPUnit_Framework_TestCase {
    public function testApplyToImage() {
        $width = 80;
        $height = 90;
        $fit = 'outbound';

        $thumbnail = m::mock('Imagine\\ImageInterface');

        $imagineImage = m::mock('Imagine\\ImageInterface');
        $imagineImage->shouldReceive('thumbnail')->once()->with(m::on(function (\Imagine\Image\Box $box) use($width, $height) {
            return $width == $box->getWidth() && $height == $box->getHeight();
        }), $fit)->andReturn($thumbnail);

        $image = m::mock('PHPIMS\\Image');
        $image->shouldReceive('getImagineImage')->once()->andReturn($imagineImage);
        $image->shouldReceive('setImagineImage')->once()->with($thumbnail);

        $transformation = new Thumbnail($width, $height, $fit);
        $transformation->applyToImage($image);
    }

    public function testApplyToImageUrl() {
        $url = m::mock('PHPIMS\\Client\\ImageUrl');
        $url->shouldReceive('append')->with(m::on(function ($string) {
            return (preg_match('/^thumbnail:/', $string) && strstr($string, 'width=100') &&
                    strstr($string, 'height=200') && strstr($string, 'fit=inset'));
        }))->once();
        $transformation = new Thumbnail(100, 200, 'inset');
        $transformation->applyToImageUrl($url);
    }
}