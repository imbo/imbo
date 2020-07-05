<?php declare(strict_types=1);
namespace Imbo\CliCommand;

use Imbo\Exception\RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\CliCommand\GeneratePrivateKey
 */
class GeneratePrivateKeyTest extends TestCase {
    private $command;

    public function setUp() : void {
        $this->command = new GeneratePrivateKey();

        $application = new Application();
        $application->add($this->command);
    }

    /**
     * @covers ::execute
     * @covers ::generate
     * @covers ::__construct
     */
    public function testCanGenerateAPrivateKey() : void {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['command' => $this->command->getName()]);

        $this->assertMatchesRegularExpression('/^[a-zA-Z_\\-0-9]{8,}$/', trim($commandTester->getDisplay()));
    }

    /**
     * @covers ::execute
     * @covers ::generate
     * @covers ::__construct
     */
    public function testFailsWhenItCantGenerateAPrivateKey() : void {
        $this->command->maxTries = 0;
        $commandTester = new CommandTester($this->command);
        $this->expectExceptionObject(new RuntimeException('Could not generate private key'));
        $commandTester->execute(['command' => $this->command->getName()]);
    }
}
