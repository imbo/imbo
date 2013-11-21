<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest;

use Imbo\Application;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 * @covers Imbo\Application
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Application
     */
    private $application;

    /**
     * Set up the application
     */
    public function setUp() {
        $this->application = new Application();
    }

    /**
     * Tear down the application
     */
    public function tearDown() {
        $this->application = null;
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid database adapter
     * @expectedExceptionCode 500
     * @covers Imbo\Application::run
     */
    public function testThrowsExceptionWhenConfigurationHasInvalidDatabaseAdapter() {
        $this->application->run(array(
            'database' => function() { return new \stdClass(); },
        ));
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid storage adapter
     * @expectedExceptionCode 500
     * @covers Imbo\Application::run
     */
    public function testThrowsExceptionWhenConfigurationHasInvalidStorageAdapter() {
        $this->application->run(array(
            'database' => $this->getMock('Imbo\Database\DatabaseInterface'),
            'storage' => function() { return new \stdClass(); },
        ));
    }

    /**
     * @covers Imbo\Application::run
     */
    public function testCanRunWithDefaultConfiguration() {
        $this->expectOutputRegex('|{"version":"dev",.*}|');
        $this->application->run(require __DIR__ . '/../../../config/config.default.php');
    }

    /**
     * @covers Imbo\Application::run
     */
    public function testCanRunWithTestingConfiguration() {
        $this->expectOutputRegex('|{"version":"dev",.*}|');
        $this->application->run(require __DIR__ . '/../../../config/config.testing.php');
    }
}
