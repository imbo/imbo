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

namespace PHPIMS\Client\ImageUrl;

use PHPIMS\Client\ImageUrl;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class TransformationTest extends \PHPUnit_Framework_TestCase {
    protected $transformation = null;
    protected $url = null;
    protected $baseUrl = 'http://host';

    public function setUp() {
        $this->transformation = new Transformation;
        $this->url = new ImageUrl($this->baseUrl . '/' . md5(microtime()) . '.png');
    }

    public function tearDown() {
        $this->transformation = null;
    }

    public function testBorder() {
        $url = (string) $this->url;
        $this->transformation->border('444', 3, 3)->apply($this->url);
        $this->assertSame($url . '?t[]=border:color=444,width=3,height=3', (string) $this->url);
    }

    public function testCrop() {
        $url = (string) $this->url;
        $this->transformation->crop(1, 2, 3, 4)->apply($this->url);
        $this->assertSame($url . '?t[]=crop:x=1,y=2,width=3,height=4', (string) $this->url);
    }

    public function testResize() {
        $url = (string) $this->url;
        $this->transformation->resize(100, 200)->apply($this->url);
        $this->assertSame($url . '?t[]=resize:width=100,height=200', (string) $this->url);
    }

    public function testRotate() {
        $url = (string) $this->url;
        $this->transformation->rotate(88, 'fff')->apply($this->url);
        $this->assertSame($url . '?t[]=rotate:angle=88,bg=fff', (string) $this->url);
    }

    public function testAll() {
        $url = (string) $this->url;
        $this->transformation->border('444', 3, 3)->crop(1, 2, 3, 4)->resize(100, 200)->rotate(88, 'fff')
                             ->border('555', 2, 2)->crop(5, 6, 7, 8)->resize(200, 100)->rotate(45, '888')->apply($this->url);

        $this->assertSame($url . '?' .
            't[]=border:color=444,width=3,height=3&t[]=crop:x=1,y=2,width=3,height=4&t[]=resize:width=100,height=200&t[]=rotate:angle=88,bg=fff&' .
            't[]=border:color=555,width=2,height=2&t[]=crop:x=5,y=6,width=7,height=8&t[]=resize:width=200,height=100&t[]=rotate:angle=45,bg=888', (string) $this->url);
    }

    public function testApplyWithNoFiltersAdded() {
        $url = (string) $this->url;
        $this->transformation->apply($this->url);
        $this->assertSame($url, (string) $this->url);
    }
}