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

namespace PHPIMS;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class ImageTest extends \PHPUnit_Framework_TestCase {
    /**
     * Image instance
     *
     * @var PHPIMS\Image
     */
    protected $image = null;

    /**
     * Set up method
     */
    public function setUp() {
        $this->image = new Image();
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->image = null;
    }

    public function testSetGetMetadata() {
        $data = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $this->image->setMetadata($data);
        $this->assertSame($data, $this->image->getMetadata());
    }

    public function testSetGetMimeType() {
        $mimeType = 'image/png';
        $this->image->setMimeType($mimeType);
        $this->assertSame($mimeType, $this->image->getMimeType());
    }

    public function testSetGetBlob() {
        $blob = 'some string';
        $this->image->setBlob($blob);
        $this->assertSame($blob, $this->image->getBlob());
    }

    public function testSetGetExtension() {
        $extension = 'png';
        $this->image->setExtension($extension);
        $this->assertSame($extension, $this->image->getExtension());
    }

    public function testSetGetWidth() {
        $width = 123;
        $this->image->setWidth($width);
        $this->assertSame($width, $this->image->getWidth());
    }

    public function testSetGetHeight() {
        $height = 234;
        $this->image->setHeight($height);
        $this->assertSame($height, $this->image->getHeight());
    }
}
