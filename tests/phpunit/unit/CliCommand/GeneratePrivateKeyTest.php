<?php declare(strict_types=1);
namespace ImboUnitTest\CliCommand;

use Imbo\CliCommand\GeneratePrivateKey;
use Imbo\Exception\RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\CliCommand\GeneratePrivateKey
 */
class GeneratePrivateKeyTest extends TestCase {
    /**
     * @var Imbo\CliCommand\GeneratePrivateKey
     */
    private $command;

    /**
     * Set up the command
     *
     * @covers Imbo\CliCommand\GeneratePrivateKey::__construct
     */
    public function setUp() : void {
        $this->command = new GeneratePrivateKey();

        $application = new Application();
        $application->add($this->command);
    }

    /**
     * @covers Imbo\CliCommand\GeneratePrivateKey::execute
     */
    public function testCanGenerateAPrivateKey() : void {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['command' => $this->command->getName()]);

        $this->assertRegExp('/^[a-zA-Z_\\-0-9]{8,}$/', trim($commandTester->getDisplay()));
    }

    /**
     * @covers Imbo\CliCommand\GeneratePrivateKey::execute
     */
    public function testFailsWhenItCantGenerateAPrivateKey() : void {
        $this->command->maxTries = 0;
        $commandTester = new CommandTester($this->command);
        $this->expectExceptionObject(new RuntimeException('Could not generate private key'));
        $commandTester->execute(['command' => $this->command->getName()]);
    }
}
