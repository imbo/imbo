<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
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
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\IntegrationTest\Database;

use Imbo\Database\Doctrine,
    PDO;

/**
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Database\Doctrine
 */
class DoctrineTest extends DatabaseTests {
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @see Imbo\IntegrationTest\Database\DatabaseTests::getDriver()
     */
    protected function getDriver() {
        return new Doctrine(array(
            'pdo' => $this->pdo,
        ));
    }

    public function setUp() {
        if (!extension_loaded('PDO')) {
            $this->markTestSkipped('PDO is required to run this test');
        }

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required to run this test');
        }

        if (!class_exists('Doctrine\DBAL\DriverManager')) {
            $this->markTestSkipped('Doctrine is required to run this test');
        }

        // Create tmp tables
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->query("
            CREATE TABLE IF NOT EXISTS imageinfo (
                id INTEGER PRIMARY KEY NOT NULL,
                publicKey TEXT NOT NULL,
                imageIdentifier TEXT NOT NULL,
                size INTEGER NOT NULL,
                extension TEXT NOT NULL,
                mime TEXT NOT NULL,
                added INTEGER NOT NULL,
                updated INTEGER NOT NULL,
                width INTEGER NOT NULL,
                height INTEGER NOT NULL,
                checksum TEXT NOT NULL,
                UNIQUE (publicKey,imageIdentifier)
            )
        ");
        $this->pdo->query("
            CREATE TABLE IF NOT EXISTS metadata (
                id INTEGER PRIMARY KEY NOT NULL,
                imageId KEY INTEGER NOT NULL,
                tagName TEXT NOT NULL,
                tagValue TEXT NOT NULL
            )
        ");

        parent::setUp();
    }

    public function tearDown() {
        $this->pdo = null;

        parent::tearDown();
    }
}
