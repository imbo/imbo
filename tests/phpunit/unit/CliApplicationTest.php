<?php declare(strict_types=1);
namespace Imbo;

use Symfony\Component\Console\Tester\ApplicationTester;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\CliApplication
 */
class CliApplicationTest extends TestCase {
    /**
     * @covers ::__construct
     */
    public function testAddsCommands() : void {
        $application = new CliApplication();
        $application->setAutoExit(false);

        $applicationTester = new ApplicationTester($application);
        $applicationTester->run(['command' => 'list']);
        $output = $applicationTester->getDisplay();

        $this->assertStringContainsString('generate-private-key', $output);
    }
}
