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
class PHPIMS_Operation_GetImageTest extends PHPUnit_Framework_TestCase {
    /**
     * Operation instance
     *
     * @var PHPIMS_Operation_GetImage
     */
    protected $operation = null;

    /**
     * Hash value sent to the database driver
     *
     * @var string
     */
    protected $hash = 'some hash value';

    /**
     * Set up method
     */
    public function setUp() {
        $this->operation = new PHPIMS_Operation_GetImage($this->hash);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->operation = null;
    }

    public function testSuccessfullExec() {
        $path = 'file:///some/path/image.png';
        $storage = $this->getMockForAbstractClass('PHPIMS_Storage_Driver_Abstract');
        $storage->expects($this->once())->method('getImagePath')->with($this->hash)->will($this->returnValue($path));
        $this->operation->setStorage($storage);

        $image = $this->getMock('PHPIMS_Image');
        $image->expects($this->once())->method('setPath')->with($path);
        $this->operation->setImage($image);

        $this->operation->exec();
    }
}