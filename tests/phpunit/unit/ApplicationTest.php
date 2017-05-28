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
    Imbo\EventListener\ListenerInterface,
    Imbo\Http\Request\Request,
    Imbo\Resource\ResourceInterface;

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
        $this->application->run([
            'database' => function() { return new \stdClass(); },
            'trustedProxies' => [],
        ]);
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid storage adapter
     * @expectedExceptionCode 500
     * @covers Imbo\Application::run
     */
    public function testThrowsExceptionWhenConfigurationHasInvalidStorageAdapter() {
        $this->application->run([
            'database' => $this->createMock('Imbo\Database\DatabaseInterface'),
            'storage' => function() { return new \stdClass(); },
            'trustedProxies' => [],
        ]);
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid access control adapter
     * @expectedExceptionCode 500
     * @covers Imbo\Application::run
     */
    public function testThrowsExceptionWhenConfigurationHasInvalidAccessControlAdapter() {
        $this->application->run([
            'database' => $this->createMock('Imbo\Database\DatabaseInterface'),
            'storage' => $this->createMock('Imbo\Storage\StorageInterface'),
            'routes' => [],
            'trustedProxies' => [],
            'accessControl' => function() { return new \stdClass(); },
        ]);
    }

    /**
     * @covers Imbo\Application::run
     */
    public function testApplicationSetsTrustedProxies() {
        $this->expectOutputRegex('|^{.*}$|');

        $this->assertEmpty(Request::getTrustedProxies());
        $this->application->run([
            'database' => $this->createMock('Imbo\Database\DatabaseInterface'),
            'storage' => $this->createMock('Imbo\Storage\StorageInterface'),
            'accessControl' => $this->createMock('Imbo\Auth\AccessControl\Adapter\AdapterInterface'),
            'eventListenerInitializers' => [],
            'eventListeners' => [],
            'contentNegotiateImages' => false,
            'resources' => [],
            'routes' => [],
            'trustedProxies' => ['10.0.0.77'],
            'indexRedirect' => null,
        ]);
        $this->assertSame(['10.0.0.77'], Request::getTrustedProxies());
    }

    /**
     * @covers Imbo\Application::run
     */
    public function testApplicationPassesRequestAndResponseToCallbacks() {
        // We just want to swallow the output, since we're testing it explicitly below.
        $this->expectOutputRegex('|.*}|');

        $default = require __DIR__ . '/../../../config/config.default.php';
        $test = array(
            'database' => function ($request, $response) {
                $this->assertInstanceOf('Imbo\Http\Request\Request', $request);
                $this->assertInstanceOf('Imbo\Http\Response\Response', $response);

                return $this->createMock('Imbo\Database\DatabaseInterface');
            },
            'storage' => function ($request, $response) {
                $this->assertInstanceOf('Imbo\Http\Request\Request', $request);
                $this->assertInstanceOf('Imbo\Http\Response\Response', $response);

                return $this->createMock('Imbo\Storage\StorageInterface');
            },
            'accessControl' => function ($request, $response) {
                $this->assertInstanceOf('Imbo\Http\Request\Request', $request);
                $this->assertInstanceOf('Imbo\Http\Response\Response', $response);

                return $this->createMock('Imbo\Auth\AccessControl\Adapter\AdapterInterface');
            },
            'eventListeners' => [
                'test' => function ($request, $response) {
                    $this->assertInstanceOf('Imbo\Http\Request\Request', $request);
                    $this->assertInstanceOf('Imbo\Http\Response\Response', $response);

                    return new TestListener();
                },
                'testSubelement' => [
                    'listener' => function ($request, $response) {
                        $this->assertInstanceOf('Imbo\Http\Request\Request', $request);
                        $this->assertInstanceOf('Imbo\Http\Response\Response', $response);

                        return new TestListener();
                    },
                ],
            ],
            'resources' => [
                'test' => function ($request, $response) {
                    $this->assertInstanceOf('Imbo\Http\Request\Request', $request);
                    $this->assertInstanceOf('Imbo\Http\Response\Response', $response);

                    return new TestResource();
                },
            ],
        );

        $this->application->run(array_merge($default, $test));
    }

    /**
     * @covers Imbo\Application::run
     */
    public function testCanRunWithDefaultConfiguration() {
        $this->expectOutputRegex('|^{.*}$|');
        $this->application->run(require __DIR__ . '/../../../config/config.default.php');
    }

    /**
     * @covers Imbo\Application::run
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage The "transformations" configuration key must be specified as an array
     */
    public function testThrowsExceptionIfTransformationsIsSetAndIsNotAnArray() {
        $defaultConfig = require __DIR__ . '/../../../config/config.default.php';
        $defaultConfig['transformations'] = function() {};
        $this->application->run($defaultConfig);
    }
}

class TestListener implements ListenerInterface {
    public static function getSubscribedEvents() {
        return [];
    }
}

class TestResource implements ListenerInterface, ResourceInterface {
    public static function getSubscribedEvents() {
        return [];
    }

    public function getAllowedMethods() {
        return [];
    }
}
