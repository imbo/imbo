<?php declare(strict_types=1);

namespace Imbo;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

#[CoversClass(CliApplication::class)]
class CliApplicationTest extends TestCase
{
    public function testAddsCommands(): void
    {
        $application = new CliApplication();
        $application->setAutoExit(false);

        $applicationTester = new ApplicationTester($application);
        $applicationTester->run(['command' => 'list']);
        $output = $applicationTester->getDisplay();

        $this->assertStringContainsString('generate-private-key', $output);
    }
}
