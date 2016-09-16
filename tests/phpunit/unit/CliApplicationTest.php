<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest;

use Imbo\CliApplication,
    Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @covers Imbo\CliApplication
 * @group unit-cli
 */
class CliApplicationTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\CliApplication::__construct
     */
    public function testAddsCommands() {
        $application = new CliApplication();
        $application->setAutoExit(false);

        $applicationTester = new ApplicationTester($application);
        $applicationTester->run(['command' => 'list']);
        $output = $applicationTester->getDisplay();

        $this->assertContains('generate-private-key', $output);
    }
}
