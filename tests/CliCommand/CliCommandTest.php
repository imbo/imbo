<?php declare(strict_types=1);

namespace Imbo\CliCommand;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CliCommand::class)]
class CliCommandTest extends TestCase
{
    private Command $command;

    protected function setUp(): void
    {
        $this->command = new Command();
    }

    public function testCanSetAndGetConfiguration(): void
    {
        $config = ['some' => 'config'];
        $this->command->setConfig($config);
        $this->assertSame($config, $this->command->getConfig());
    }

    public function testFetchesTheDefaultConfigurationIfNoneHasBeenSet(): void
    {
        $this->assertEquals(require __DIR__.'/../../config/config.default.php', $this->command->getConfig());
    }

    public function testCanSetConfig(): void
    {
        $this->command->setConfig(['some' => 'config']);
        $this->assertSame(['some' => 'config'], $this->command->getConfig());
    }
}

class Command extends CliCommand
{
}
