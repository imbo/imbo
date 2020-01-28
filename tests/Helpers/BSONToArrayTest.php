<?php declare(strict_types=1);
namespace Imbo\Helpers;

use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Helpers\BSONToArray
 */
class BSONToArrayTest extends TestCase {
    private $helper;

    public function setUp() : void {
        $this->helper = new BSONToArray();
    }

    public function getValues() : array {
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
     * @covers ::toArray
     * @covers ::isBSONModel
     */
    public function testCanConvertValuesToArray($document, $expected) : void {
        $this->assertSame($expected, $this->helper->toArray($document));
    }
}
