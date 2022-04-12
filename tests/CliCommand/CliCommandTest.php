<?php declare(strict_types=1);
namespace Imbo\CliCommand;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\CliCommand\CliCommand
 */
class CliCommandTest extends TestCase
{
    private $command;

    public function setUp(): void
    {
        $this->command = $this
            ->getMockBuilder(CliCommand::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    /**
     * @covers ::getConfig
     */
    public function testCanSetAndGetConfiguration(): void
    {
        $config = ['some' => 'config'];
        $this->command->setConfig($config);
        $this->assertSame($config, $this->command->getConfig());
    }

    /**
     * @covers ::getConfig
     */
    public function testFetchesTheDefaultConfigurationIfNoneHasBeenSet(): void
    {
        $this->assertEquals(require __DIR__ . '/../../config/config.default.php', $this->command->getConfig());
    }

    /**
     * @covers ::getConfig
     * @covers ::setConfig
     */
    public function testCanSetConfig(): void
    {
        $this->command->setConfig(['some' => 'config']);
        $this->assertSame(['some' => 'config'], $this->command->getConfig());
    }
}
