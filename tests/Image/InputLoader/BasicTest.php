<?php declare(strict_types=1);

namespace Imbo\Image\InputLoader;

use Imagick;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Basic::class)]
class BasicTest extends TestCase
{
    private Basic $loader;

    protected function setUp(): void
    {
        $this->loader = new Basic();
    }

    public function testReturnsSupportedMimeTypes(): void
    {
        $types = $this->loader->getSupportedMimeTypes();
        $this->assertContains('image/png', array_keys($types));
        $this->assertContains('image/jpeg', array_keys($types));
        $this->assertContains('image/gif', array_keys($types));
        $this->assertContains('image/tiff', array_keys($types));
    }

    public function testLoadsImage(): void
    {
        $blob = file_get_contents(FIXTURES_DIR.'/1024x256.png');

        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('readImageBlob')
            ->with($blob);

        $this->assertNull($this->loader->load($imagick, $blob, 'image/png'));
    }
}
