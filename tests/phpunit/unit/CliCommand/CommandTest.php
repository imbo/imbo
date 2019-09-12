<?php
namespace ImboUnitTest\CliCommand;

use Imbo\CliCommand\CliCommand;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\CliCommand\CliCommand
 */
class CliCommandTest extends TestCase {
    /**
     * @var Imbo\CliCommand\CliCommand
     */
    private $command;

    /**
     * Set up the command
     */
    public function setUp() : void {
        $this->command = $this->getMockBuilder('Imbo\CliCommand\CliCommand')->disableOriginalConstructor()->getMockForAbstractClass();
    }

    public function testCanSetAndGetConfiguration() {
        $config = ['some' => 'config'];
        $this->command->setConfig($config);
        $this->assertSame($config, $this->command->getConfig());
    }

    public function testFetchesTheDefaultConfigurationIfNoneHasBeenSet() {
        $this->assertEquals(require __DIR__ . '/../../../../config/config.default.php', $this->command->getConfig());
    }
}
