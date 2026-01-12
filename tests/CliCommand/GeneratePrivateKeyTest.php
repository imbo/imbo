<?php declare(strict_types=1);

namespace Imbo\CliCommand;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(GeneratePrivateKey::class)]
class GeneratePrivateKeyTest extends TestCase
{
    private GeneratePrivateKey $command;

    protected function setUp(): void
    {
        $this->command = new GeneratePrivateKey();

        $application = new Application();
        $application->addCommand($this->command);
    }

    public function testCanGenerateAPrivateKey(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['command' => $this->command->getName()]);

        $this->assertMatchesRegularExpression('/^[a-zA-Z_\\-0-9]{8,}$/', trim($commandTester->getDisplay()));
    }
}
