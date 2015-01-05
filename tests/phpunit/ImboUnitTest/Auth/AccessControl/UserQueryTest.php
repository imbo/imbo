<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Auth\UserLookup;

use Imbo\Auth\UserLookup\Query;

/**
 * @covers Imbo\Auth\UserLookup\Query
 * @group unit
 */
class QueryTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Query
     */
    private $query;

    /**
     * Set up the query
     */
    public function setUp() {
        $this->query = new Query();
    }

    /**
     * Tear down the query
     */
    public function tearDown() {
        $this->query = null;
    }

    public function testCanSetAndGetALimit() {
        $this->assertNull($this->query->limit());
        $this->assertSame($this->query, $this->query->limit(123));
        $this->assertSame(123, $this->query->limit());
    }

    public function testCanSetAndGetOffset() {
        $this->assertNull($this->query->offset());
        $this->assertSame($this->query, $this->query->offset(123));
        $this->assertSame(123, $this->query->offset());
    }
}
