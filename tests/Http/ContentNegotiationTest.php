<?php declare(strict_types=1);
namespace Imbo\Http;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Http\ContentNegotiation
 */
class ContentNegotiationTest extends TestCase
{
    private ContentNegotiation $cn;

    public function setUp(): void
    {
        $this->cn = new ContentNegotiation();
    }

    /**
     * @dataProvider getIsAcceptableData
     * @covers ::isAcceptable
     */
    public function testCanCheckIfAMimeTypeIsAcceptable(string $mimeType, array $acceptable, float|bool $result): void
    {
        $this->assertSame($result, $this->cn->isAcceptable($mimeType, $acceptable));
    }

    /**
     * @dataProvider getMimeTypes
     * @covers ::bestMatch
     * @covers ::isAcceptable
     */
    public function testCanPickTheBestMatchFromASetOfMimeTypes(array $mimeTypes, array $acceptable, string|bool $result): void
    {
        $this->assertSame($result, $this->cn->bestMatch($mimeTypes, $acceptable));
    }

    /**
     * @return array<array{mimeType:string,acceptable:array<string,double>,result:double|bool}>
     */
    public static function getIsAcceptableData(): array
    {
        return [
            [
                'mimeType' => 'image/png',
                'acceptable' => ['image/png' => 1.0, 'image/*' => 0.9],
                'result' => 1.0,
            ],
            [
                'mimeType' => 'image/png',
                'acceptable' => ['text/html' => 1, '*/*' => 0.9],
                'result' => 0.9,
            ],
            [
                'mimeType' => 'image/png',
                'acceptable' => ['text/html' => 1],
                'result' => false,
            ],
            [
                'mimeType' => 'image/jpeg',
                'acceptable' => ['application/json' => 1, 'text/*' => 0.9],
                'result' => false,
            ],
            [
                'mimeType' => 'application/json',
                'acceptable' => ['text/html;level=1' => 1, 'text/html' => 0.9, '*/*' => 0.8, 'text/html;level=2' => 0.7, 'text/*' => 0.9],
                'result' => 0.8,
            ],
        ];
    }

    /**
     * @return array<array{mimeTypes:array<string>,acceptable:array<string,double>,result:string|bool}>
     */
    public static function getMimeTypes(): array
    {
        return [
            [
                'mimeTypes' => ['image/png', 'image/gif'],
                'acceptable' => ['image/*' => 1],
                'result' => 'image/png',
            ],
            [
                'mimeTypes' => ['image/png', 'image/gif'],
                'acceptable' => ['image/png' => 0.9, 'image/gif' => 1],
                'result' => 'image/gif',
            ],
            [
                'mimeTypes' => ['image/png', 'image/gif'],
                'acceptable' => ['application/json' => 1, 'image/*' => 0.9],
                'result' => 'image/png',
            ],
            [
                'mimeTypes' => ['image/png', 'image/gif'],
                'acceptable' => ['application/json' => 1],
                'result' => false,
            ],
        ];
    }
}
