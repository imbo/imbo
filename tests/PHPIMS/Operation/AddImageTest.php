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
class PHPIMS_Operation_AddImageTest extends PHPUnit_Framework_TestCase {
    /**
     * Operation instance
     *
     * @var PHPIMS_Operation_AddImage
     */
    protected $operation = null;

    /**
     * Set up method
     */
    public function setUp() {
        $this->operation = new PHPIMS_Operation_AddImage();

        $_FILES['file'] = array(
            'name'     => 'somename',
            'tmp_name' => '/tmp/foobar',
            'size'     => 42,
        );

        $_SERVER['HTTP_HOST'] = 'some host';
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->operation = null;

        unset($_FILES['file']);
        unset($_SERVER['HTTP_HOST']);
    }

    /**
     * @expectedException PHPIMS_Operation_Exception
     * @expectedExceptionMessage Unable to add image to the database
     */
    public function testExecWhenDatabaseFails() {
        $database = $this->getMockForAbstractClass('PHPIMS_Database_Driver_Abstract');
        $database->expects($this->once())->method('insertNewImage')->will($this->throwException(new PHPIMS_Database_Exception()));
        $this->operation->setDatabase($database);

        $this->operation->exec();
    }

    /**
     * @expectedException PHPIMS_Operation_Exception
     * @expectedExceptionMessage Unable to store the image
     */
    public function testExecWhenStorageFails() {
        $database = $this->getMockForAbstractClass('PHPIMS_Database_Driver_Abstract');
        $database->expects($this->once())->method('insertNewImage');
        $this->operation->setDatabase($database);

        $storage = $this->getMockForAbstractClass('PHPIMS_Storage_Driver_Abstract');
        $storage->expects($this->once())->method('store')->with($_FILES['file']['tmp_name'])->will($this->throwException(new PHPIMS_Storage_Exception()));
        $this->operation->setStorage($storage);

        $this->operation->exec();
    }

    public function testSuccessfullExec() {
        $database = $this->getMockForAbstractClass('PHPIMS_Database_Driver_Abstract');
        $database->expects($this->once())->method('insertNewImage');
        $this->operation->setDatabase($database);

        $storage = $this->getMockForAbstractClass('PHPIMS_Storage_Driver_Abstract');
        $storage->expects($this->once())->method('store')->with($_FILES['file']['tmp_name']);
        $this->operation->setStorage($storage);

        $this->operation->exec();
    }
}