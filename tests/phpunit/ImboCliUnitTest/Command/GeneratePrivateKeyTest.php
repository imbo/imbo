<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboCliUnitTest\Command;

use ImboCli\Command\GeneratePrivateKey,
    Symfony\Component\Console\Application,
    Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers ImboCli\Command\GeneratePrivateKey
 * @group unit-cli
 * @group cli-commands
 */
class GeneratePrivateKeyTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ImboCli\Command\GeneratePrivateKey
     */
    private $command;

    /**
     * Set up the command
     *
     * @covers ImboCli\Command\GeneratePrivateKey::__construct
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
     * @covers ImboCli\Command\GeneratePrivateKey::execute
     */
    public function testCanGenerateAPrivateKey() {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['command' => $this->command->getName()]);

        $this->assertRegExp('/^[a-zA-Z_\\-0-9]{8,}$/', trim($commandTester->getDisplay()));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not generate private key
     * @covers ImboCli\Command\GeneratePrivateKey::execute
     */
    public function testFailsWhenItCantGenerateAPrivateKey() {
        $this->command->maxTries = 0;
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['command' => $this->command->getName()]);
    }
}
