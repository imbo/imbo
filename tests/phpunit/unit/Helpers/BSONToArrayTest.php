<?php
namespace ImboUnitTest\Helpers;

use Imbo\Helpers\BSONToArray;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Helpers\BSONToArray
 */
class BSONToArrayTest extends TestCase {
    /**
     * @var BSONToArray
     */
    private $helper;

    /**
     * Set up the helper
     */
    public function setUp() : void {
        $this->helper = new BSONToArray();
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
