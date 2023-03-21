<?php declare(strict_types=1);
namespace Imbo\Helpers;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Helpers\Urls
 */
class UrlsTest extends TestCase
{
    private Urls $helper;

    public function setUp(): void
    {
        $this->helper = new Urls();
    }

    /**
     * @return array<array{0:string}>
     */
    public static function getUrls(): array
    {
        return [
            ['http://localhost'],
            ['http://example.com/path?foo=bar&bar=foo#yo'],
            ['https://user:pass@example.com:123/path?foo=bar&bar=foo#yo'],
        ];
    }

    /**
     * @dataProvider getUrls
     * @covers ::buildFromParseUrlParts
     */
    public function testCanBuildFromParts(string $url): void
    {
        $this->assertSame($url, $this->helper->buildFromParseUrlParts(parse_url($url)));
    }
}
