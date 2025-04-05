<?php declare(strict_types=1);
namespace Imbo\CliCommand;

use Imbo\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(GeneratePrivateKey::class)]
class GeneratePrivateKeyTest extends TestCase
{
    private GeneratePrivateKey $command;

    public function setUp(): void
    {
        $this->command = new GeneratePrivateKey();

        $application = new Application();
        $application->add($this->command);
    }

    public function testCanGenerateAPrivateKey(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['command' => $this->command->getName()]);

        $this->assertMatchesRegularExpression('/^[a-zA-Z_\\-0-9]{8,}$/', trim($commandTester->getDisplay()));
    }

    public function testFailsWhenItCantGenerateAPrivateKey(): void
    {
        $this->command->maxTries = 0;
        $commandTester = new CommandTester($this->command);
        $this->expectExceptionObject(new RuntimeException('Could not generate private key'));
        $commandTester->execute(['command' => $this->command->getName()]);
    }
}
