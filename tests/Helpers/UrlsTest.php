<?php declare(strict_types=1);

namespace Imbo\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Urls::class)]
class UrlsTest extends TestCase
{
    private Urls $helper;

    protected function setUp(): void
    {
        $this->helper = new Urls();
    }

    #[DataProvider('getUrls')]
    public function testCanBuildFromParts(string $url): void
    {
        $this->assertSame($url, $this->helper->buildFromParseUrlParts(parse_url($url)));
    }

    /**
     * @return array<array{url:string}>
     */
    public static function getUrls(): array
    {
        return [
            [
                'url' => 'http://localhost',
            ],
            [
                'url' => 'http://example.com/path?foo=bar&bar=foo#yo',
            ],
            [
                'url' => 'https://user:pass@example.com:123/path?foo=bar&bar=foo#yo',
            ],
        ];
    }
}
