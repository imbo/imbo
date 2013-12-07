<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Http;

use Imbo\Http\ContentNegotiation;

/**
 * @covers Imbo\Http\ContentNegotiation
 * @group unit
 * @group http
 */
class ContentNegotiationTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ContentNegotiation
     */
    private $cn;

    /**
     * Set up
     */
    public function setUp() {
        $this->cn = new ContentNegotiation();
    }

    /**
     * Tear down
     */
    public function tearDown() {
        $this->cn = null;
    }

    /**
     * @return array[]
     */
    public function getIsAcceptableData() {
        return array(
            array('image/png', array('image/png' => 1, 'image/*' => 0.9), 1),
            array('image/png', array('text/html' => 1, '*/*' => 0.9), 0.9),
            array('image/png', array('text/html' => 1), false),
            array('image/jpeg', array('application/json' => 1, 'text/*' => 0.9), false),
            array('application/json', array('text/html;level=1' => 1, 'text/html' => 0.9, '*/*' => 0.8, 'text/html;level=2' => 0.7, 'text/*' => 0.9), 0.8),
        );
    }

    /**
     * @dataProvider getIsAcceptableData
     * @covers Imbo\Http\ContentNegotiation::isAcceptable
     */
    public function testCanCheckIfAMimeTypeIsAcceptable($mimeType, $acceptable, $result) {
        $this->assertSame($result, $this->cn->isAcceptable($mimeType, $acceptable));
    }

    /**
     * @return array[]
     */
    public function getMimeTypes() {
        return array(
            array(array('image/png', 'image/gif'), array('image/*' => 1), 'image/png'),
            array(array('image/png', 'image/gif'), array('image/png' => 0.9, 'image/gif' => 1), 'image/gif'),
            array(array('image/png', 'image/gif'), array('application/json' => 1, 'image/*' => 0.9), 'image/png'),
            array(array('image/png', 'image/gif'), array('application/json' => 1), false),
        );
    }

    /**
     * @dataProvider getMimeTypes
     * @covers Imbo\Http\ContentNegotiation::bestMatch
     * @covers Imbo\Http\ContentNegotiation::isAcceptable
     */
    public function testCanPickTheBestMatchFromASetOfMimeTypes($mimeTypes, $acceptable, $result) {
        $this->assertSame($result, $this->cn->bestMatch($mimeTypes, $acceptable));
    }
}
