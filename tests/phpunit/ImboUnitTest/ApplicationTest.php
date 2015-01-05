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

use Imbo\Application,
    Imbo\Version,
    Imbo\Http\Request\Request;

/**
 * @covers Imbo\Application
 * @group unit
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
            'trustedProxies' => [],
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
            'trustedProxies' => [],
        ));
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid access control adapter
     * @expectedExceptionCode 500
     * @covers Imbo\Application::run
     */
    public function testThrowsExceptionWhenConfigurationHasInvalidAccessControlAdapter() {
        $this->application->run(array(
            'database' => $this->getMock('Imbo\Database\DatabaseInterface'),
            'storage' => $this->getMock('Imbo\Storage\StorageInterface'),
            'routes' => [],
            'trustedProxies' => [],
            'accessControl' => function() { return new \stdClass(); },
        ));
    }

    /**
     * @covers Imbo\Application::run
     */
    public function testApplicationSetsTrustedProxies() {
        $this->expectOutputRegex('|{"version":"' . preg_quote(Version::VERSION, '|') . '",.*}|');

        $this->assertEmpty(Request::getTrustedProxies());
        $this->application->run(array(
            'database' => $this->getMock('Imbo\Database\DatabaseInterface'),
            'storage' => $this->getMock('Imbo\Storage\StorageInterface'),
            'accessControl' => $this->getMock('Imbo\Auth\AccessControl\AccessControlInterface'),
            'eventListenerInitializers' => [],
            'eventListeners' => [],
            'contentNegotiateImages' => false,
            'resources' => [],
            'routes' => [],
            'auth' => [],
            'trustedProxies' => ['10.0.0.77'],
        ));
        $this->assertSame(['10.0.0.77'], Request::getTrustedProxies());
    }

    /**
     * @covers Imbo\Application::run
     */
    public function testCanRunWithDefaultConfiguration() {
        $this->expectOutputRegex('|{"version":"' . preg_quote(Version::VERSION, '|') . '",.*}|');
        $this->application->run(require __DIR__ . '/../../../config/config.default.php');
    }
}
