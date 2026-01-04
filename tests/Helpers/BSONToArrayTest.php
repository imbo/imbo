<?php declare(strict_types=1);
namespace Imbo\Helpers;

use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BSONToArray::class)]
class BSONToArrayTest extends TestCase
{
    /**
     * @param BSONDocument|BSONArray|array<mixed> $document
     * @param array<mixed> $expected
     */
    #[DataProvider('getValues')]
    public function testCanConvertValuesToArray(BSONDocument|BSONArray|array $document, array $expected): void
    {
        $this->assertSame($expected, (new BSONToArray())->toArray($document));
    }

    /**
     * @return array<string,array{document:BSONDocument|BSONArray|array<mixed>,expected:array<mixed>}>
     */
    public static function getValues(): array
    {
        return [
            'simple bson-array' => [
                'document' => new BSONArray([1, 2, 3]),
                'expected' => [1, 2, 3],
            ],
            'simple bson-document' => [
                'document' => new BSONDocument([
                    'integer' => 1,
                    'string' => 'string',
                    'boolean' => true,
                    'double' => 1.1,
                ]),
                'expected' => [
                    'integer' => 1,
                    'string' => 'string',
                    'boolean' => true,
                    'double' => 1.1,
                ],
            ],
            'nested bson-document' => [
                'document' => new BSONDocument([
                    'list' => new BSONArray([1, 2, 3]),
                    'document' => new BSONDocument([
                        'list' => new BSONArray([1, 2, 3]),
                        'document' => new BSONDocument([
                            'foo' => 'bar',
                        ]),
                    ]),
                ]),
                'expected' => [
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
}
