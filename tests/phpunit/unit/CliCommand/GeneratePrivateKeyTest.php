<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\CliCommand;

use Imbo\CliCommand\GeneratePrivateKey;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\CliCommand\GeneratePrivateKey
 * @group unit-cli
 * @group cli-commands
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
    public function setUp() {
        $this->command = new GeneratePrivateKey();

        $application = new Application();
        $application->add($this->command);
    }

    /**
     * Tear down the command
     */
    public function tearDown() {
        $this->command = null;
    }

    /**
     * @covers Imbo\CliCommand\GeneratePrivateKey::execute
     */
    public function testCanGenerateAPrivateKey() {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['command' => $this->command->getName()]);

        $this->assertRegExp('/^[a-zA-Z_\\-0-9]{8,}$/', trim($commandTester->getDisplay()));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not generate private key
     * @covers Imbo\CliCommand\GeneratePrivateKey::execute
     */
    public function testFailsWhenItCantGenerateAPrivateKey() {
        $this->command->maxTries = 0;
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['command' => $this->command->getName()]);
    }
}
