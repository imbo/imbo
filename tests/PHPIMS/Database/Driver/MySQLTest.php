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
 * @author Mats Lindh <mats@lindh.no>
 * @copyright Copyright (c) 2011, Mats Lindh
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Database\Driver;

use \Mockery as m;

/**
 * Unit tests for the MySQL/PDO-driver. Based on tests for the MongoDB driver.
 *
 * @package PHPIMS
 * @subpackage Unittests
 * @author Mats Lindh <mats@lindh.no>
 * @copyright Copyright (c) 2011, Mats Lindh
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class MySQLTest extends \PHPUnit_Framework_TestCase {
    /**
     * Driver instance
     *
     * @var PHPIMS\Database\Driver\MySQL
     */
    protected $driver = null;

    protected $pdo = null;

    /**
     * Parameters for the driver
     */
    protected $driverParams = array(
        'databaseName'   => 'phpims_test',
        'collectionName' => 'images_test',
    );

    /**
     * Set up method
     */
    public function setUp() {
        $this->pdo = m::mock();
        $this->driver = new MySQL($this->driverParams, $this->pdo);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->driver = null;
    }

    /**
     * @expectedException PHPIMS\Database\Exception
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Image already exists
     */
    public function testInsertImageThatAlreadyExists() {
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358.jpeg';
        $data = array('imageIdentifier' => $imageIdentifier);

        $image = m::mock('PHPIMS\\Image');
        $statement = m::mock();
        
        $statement->shouldReceive('execute', 'closeCursor')->once();
        $statement->shouldReceive('fetch')->once()->andReturn(array(1));
        
        $this->pdo->shouldReceive('prepare')->once()->andReturn($statement);

        $this->driver->insertImage($imageIdentifier, $image);
    }
    
    public function testSucessfullInsert() {
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358.jpeg';
        $data = array('imageIdentifier' => $imageIdentifier);

        $image = m::mock('PHPIMS\\Image');
        $image->shouldReceive('getFilename', 'getFilesize', 'getMimeType', 'getWidth', 'getHeight')
              ->once();

        $existsStatement = m::mock();
        
        $existsStatement->shouldReceive('execute', 'closeCursor')->once();
        $existsStatement->shouldReceive('fetch')->once()->andReturn(null);
        
        $insertStatement = m::mock();
        $insertStatement->shouldReceive('execute', 'closeCursor')->once();
        
        $this->pdo->shouldReceive('prepare')->times(2)->andReturn($existsStatement, $insertStatement);

        $result = $this->driver->insertImage($imageIdentifier, $image);
        $this->assertTrue($result);
    }
}
