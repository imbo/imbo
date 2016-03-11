<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest;

use Imbo\Helpers\BSONToArray,
    MongoDB\Model\BSONArray,
    MongoDB\Model\BSONDocument;

/**
 * @covers Imbo\Helpers\BSONToArray
 * @group unit
 * @group helpers
 */
class BSONToArrayTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var BSONToArray
     */
    private $helper;

    /**
     * Set up the helper
     */
    public function setUp() {
        $this->helper = new BSONToArray();
    }

    /**
     * Tear down the helper
     */
    public function tearDown() {
        $this->helper = null;
    }

    /**
     * Get different values to test
     *
     * @return array[]
     */
    public function getValues() {
        return [
            'string value' => [
                'string',
                'string',
            ],
            'integer value' => [
                1,
                1,
            ],
            'float value' => [
                [1.1],
                [1.1],
            ],
            'true boolean value' => [
                true,
                true,
            ],
            'false boolean value' => [
                false,
                false,
            ],
            'list value' => [
                [1, 2],
                [1, 2],
            ],
            'simple bson-array' => [
                new BSONArray([1, 2, 3]),
                [1, 2, 3],
            ],
            'simple bson-document' => [
                new BSONDocument([
                    'integer' => 1,
                    'string' => 'string',
                    'boolean' => true,
                    'double' => 1.1
                ]),
                [
                    'integer' => 1,
                    'string' => 'string',
                    'boolean' => true,
                    'double' => 1.1
                ],
            ],
            'nested bson-document' => [
                new BSONDocument([
                    'list' => new BSONArray([1, 2, 3]),
                    'document' => new BSONDocument([
                        'list' => new BSONArray([1, 2, 3]),
                         'document' => new BSONDocument([
                             'foo' => 'bar',
                         ]),
                    ]),
                ]),
                [
                    'list' => [1, 2, 3],
                    'document' => [
                        'list' => [1, 2, 3],
                        'document' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getValues
     * @covers Imbo\Helpers\BSONToArray::toArray
     * @covers Imbo\Helpers\BSONToArray::isBSONModel
     */
    public function testCanConvertValuesToArray($document, $expected) {
        $this->assertSame($expected, $this->helper->toArray($document));
    }
}
