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

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_ImageTest extends PHPUnit_Framework_TestCase {
    /**
     * Image instance
     *
     * @var PHPIMS_Image
     */
    protected $image = null;

    /**
     * Set up method
     */
    public function setUp() {
        $this->image = new PHPIMS_Image();
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->image = null;
    }

    public function testSetGetId() {
        $id = '123123213';
        $this->image->setId($id);
        $this->assertSame($id, $this->image->getId());
    }

    public function testSetGetFilename() {
        $name = 'someName.jpg';
        $this->image->setFilename($name);
        $this->assertSame($name, $this->image->getFilename());
    }

    public function testSetGetFilesize() {
        $size = 9854;
        $this->image->setFilesize($size);
        $this->assertSame($size, $this->image->getFilesize());
    }

    public function testSetGetMetadata() {
        $data = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $this->image->setMetadata($data);
        $this->assertSame($data, $this->image->getMetadata());
    }

    public function testSetGetPath() {
        $path = '/some/path.jpg';
        $this->image->setPath($path);
        $this->assertSame($path, $this->image->getPath());
    }

    public function testSetGetMimeType() {
        $mimeType = 'image/png';
        $this->image->setMimeType($mimeType);
        $this->assertSame($mimeType, $this->image->getMimeType());
    }

    public function testSetGetMd5() {
        $md5 = md5(microtime());
        $this->image->setMd5($md5);
        $this->assertSame($md5, $this->image->getMd5());
    }
}