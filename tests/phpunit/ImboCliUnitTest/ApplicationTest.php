<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboCliUnitTest;

use ImboCli\Application,
    Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @covers ImboCli\Application
 * @group unit-cli
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers ImboCli\Application::__construct
     */
    public function testAddsCommands() {
        $application = new Application();
        $application->setAutoExit(false);

        $applicationTester = new ApplicationTester($application);
        $applicationTester->run(['command' => 'list']);
        $output = $applicationTester->getDisplay();

        $this->assertContains('generate-private-key', $output);
    }
}
