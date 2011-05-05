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

namespace PHPIMS\Image;

use PHPIMS\Client\ImageUrl;
use \Mockery as m;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class TransformationChainTest extends \PHPUnit_Framework_TestCase {
    private $chain = null;
    private $url = null;
    private $baseUrl = 'http://host';

    public function setUp() {
        $this->chain = new TransformationChain();
        $this->url = new ImageUrl($this->baseUrl . '/' . md5(microtime()) . '.png');
    }

    public function tearDown() {
        $this->chain = null;
    }

    public function testBorder() {
        $url = (string) $this->url;
        $this->assertInstanceOf('PHPIMS\\Image\\TransformationChain', $this->chain->border('444', 3, 3)->applyToImageUrl($this->url));
        $this->assertSame($url . '?t[]=border:color=444,width=3,height=3', (string) $this->url);
    }

    public function testCrop() {
        $url = (string) $this->url;
        $this->assertInstanceOf('PHPIMS\\Image\\TransformationChain', $this->chain->crop(1, 2, 3, 4)->applyToImageUrl($this->url));
        $this->assertSame($url . '?t[]=crop:x=1,y=2,width=3,height=4', (string) $this->url);
    }

    public function testResize() {
        $url = (string) $this->url;
        $this->assertInstanceOf('PHPIMS\\Image\\TransformationChain', $this->chain->resize(100, 200)->applyToImageUrl($this->url));
        $this->assertSame($url . '?t[]=resize:width=100,height=200', (string) $this->url);
    }

    public function testRotate() {
        $url = (string) $this->url;
        $this->assertInstanceOf('PHPIMS\\Image\\TransformationChain', $this->chain->rotate(88, 'fff')->applyToImageUrl($this->url));
        $this->assertSame($url . '?t[]=rotate:angle=88,bg=fff', (string) $this->url);
    }

    public function testThumbnail() {
        $url = (string) $this->url;
        $this->assertInstanceOf('PHPIMS\\Image\\TransformationChain', $this->chain->thumbnail(60, 60, 'inset')->applyToImageUrl($this->url));
        $this->assertSame($url . '?t[]=thumbnail:width=60,height=60,fit=inset', (string) $this->url);
    }

    public function testFlipHorizontally() {
        $url = (string) $this->url;
        $this->assertInstanceOf('PHPIMS\\Image\\TransformationChain', $this->chain->flipHorizontally()->applyToImageUrl($this->url));
        $this->assertSame($url . '?t[]=flipHorizontally', (string) $this->url);
    }

    public function testFlipVertically() {
        $url = (string) $this->url;
        $this->assertInstanceOf('PHPIMS\\Image\\TransformationChain', $this->chain->flipVertically()->applyToImageUrl($this->url));
        $this->assertSame($url . '?t[]=flipVertically', (string) $this->url);
    }

    public function testApplyToImageUrlWithNoFiltersAdded() {
        $url = (string) $this->url;
        $this->chain->applyToImageUrl($this->url);
        $this->assertSame($url, (string) $this->url);
    }

    public function testTransformImage() {
        $image = m::mock('Imagine\\ImageInterface');
        $transformation = m::mock('PHPIMS\\Image\\TransformationInterface');
        $transformation->shouldReceive('applyToImage')->once()->with($image);

        $this->chain->transformImage($image, $transformation);
    }

    public function testTransformImageUrl() {
        $transformation = m::mock('PHPIMS\\Image\\TransformationInterface');
        $transformation->shouldReceive('getUrlTrigger')->once()->andReturn('trigger');

        $url = m::mock('PHPIMS\\Client\\ImageUrl');
        $url->shouldReceive('append')->once()->with('trigger');

        $this->chain->transformImageUrl($url, $transformation);
    }
}