<?php declare(strict_types=1);
namespace Imbo;

use Imbo\Auth\AccessControl\Adapter\AdapterInterface;
use Imbo\Database\DatabaseInterface;
use Imbo\EventListener\ListenerInterface;
use Imbo\Http\Request\Request;
use Imbo\Resource\ResourceInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;
use Imbo\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @coversDefaultClass Imbo\Application
 */
class ApplicationTest extends TestCase {
    private $application;

    public function setUp() : void {
        $this->application = new Application();
    }

    /**
     * @covers ::run
     */
    public function testThrowsExceptionWhenConfigurationHasInvalidDatabaseAdapter() : void {
        $this->expectExceptionObject(new InvalidArgumentException('Invalid database adapter', 500));
        $this->application->run([
            'database' => function() { return new stdClass(); },
            'trustedProxies' => [],
        ]);
    }

    /**
     * @covers ::run
     */
    public function testThrowsExceptionWhenConfigurationHasInvalidStorageAdapter() : void {
        $this->expectExceptionObject(new InvalidArgumentException('Invalid storage adapter', 500));
        $this->application->run([
            'database' => $this->createMock('Imbo\Database\DatabaseInterface'),
            'storage' => function() { return new stdClass(); },
            'trustedProxies' => [],
        ]);
    }

    /**
     * @covers ::run
     */
    public function testThrowsExceptionWhenConfigurationHasInvalidAccessControlAdapter() : void {
        $this->expectExceptionObject(new InvalidArgumentException('Invalid access control adapter', 500));
        $this->application->run([
            'database' => $this->createMock(DatabaseInterface::class),
            'storage' => $this->createMock(StorageInterface::class),
            'routes' => [],
            'trustedProxies' => [],
            'accessControl' => function() { return new stdClass(); },
        ]);
    }

    /**
     * @covers ::run
     */
    public function testApplicationSetsTrustedProxies() : void {
        $this->expectOutputRegex('|^{.*}$|');

        $this->assertEmpty(Request::getTrustedProxies());
        $this->application->run([
            'database' => $this->createMock(DatabaseInterface::class),
            'storage' => $this->createMock(StorageInterface::class),
            'accessControl' => $this->createMock(AdapterInterface::class),
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
     * @covers ::run
     */
    public function testApplicationPassesRequestAndResponseToCallbacks() : void {
        // We just want to swallow the output, since we're testing it explicitly below.
        $this->expectOutputRegex('|.*}|');

        $default = require __DIR__ . '/../../../config/config.default.php';
        $test = array(
            'database' => function ($request, $response) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(Response::class, $response);

                return $this->createMock(DatabaseInterface::class);
            },
            'storage' => function ($request, $response) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(Response::class, $response);

                return $this->createMock(StorageInterface::class);
            },
            'accessControl' => function ($request, $response) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(Response::class, $response);

                return $this->createMock(AdapterInterface::class);
            },
            'eventListeners' => [
                'test' => function ($request, $response) {
                    $this->assertInstanceOf(Request::class, $request);
                    $this->assertInstanceOf(Response::class, $response);

                    return new TestListener();
                },
                'testSubelement' => [
                    'listener' => function ($request, $response) {
                        $this->assertInstanceOf(Request::class, $request);
                        $this->assertInstanceOf(Response::class, $response);

                        return new TestListener();
                    },
                ],
            ],
            'resources' => [
                'test' => function ($request, $response) {
                    $this->assertInstanceOf(Request::class, $request);
                    $this->assertInstanceOf(Response::class, $response);

                    return new TestResource();
                },
            ],
        );

        $this->application->run(array_merge($default, $test));
    }

    /**
     * @covers ::run
     */
    public function testCanRunWithDefaultConfiguration() : void {
        $this->expectOutputRegex('|^{.*}$|');
        $this->application->run(require __DIR__ . '/../../../config/config.default.php');
    }

    /**
     * @covers ::run
     */
    public function testThrowsExceptionIfTransformationsIsSetAndIsNotAnArray() : void {
        $defaultConfig = require __DIR__ . '/../../../config/config.default.php';
        $defaultConfig['transformations'] = function() {};
        $this->expectExceptionObject(new InvalidArgumentException(
            'The "transformations" configuration key must be specified as an array',
            500
        ));
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
