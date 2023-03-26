<?php declare(strict_types=1);
namespace Imbo;

use Imbo\Auth\AccessControl\Adapter\AdapterInterface;
use Imbo\Database\DatabaseInterface;
use Imbo\EventListener\ListenerInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Resource\ResourceInterface;
use Imbo\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @coversDefaultClass Imbo\Application
 */
class ApplicationTest extends TestCase
{
    private function runImbo(array $config): void
    {
        (new Application($config))->run();
    }

    /**
     * @covers ::run
     */
    public function testThrowsExceptionWhenConfigurationHasInvalidDatabaseAdapter(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Invalid database adapter', Response::HTTP_INTERNAL_SERVER_ERROR));
        $this->runImbo([
            'database' => function () {
                return new stdClass();
            },
            'trustedProxies' => [],
        ]);
    }

    /**
     * @covers ::run
     */
    public function testThrowsExceptionWhenConfigurationHasInvalidStorageAdapter(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Invalid storage adapter', Response::HTTP_INTERNAL_SERVER_ERROR));
        $this->runImbo([
            'database' => $this->createMock(DatabaseInterface::class),
            'storage' => function () {
                return new stdClass();
            },
            'trustedProxies' => [],
        ]);
    }

    /**
     * @covers ::run
     */
    public function testThrowsExceptionWhenConfigurationHasInvalidAccessControlAdapter(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Invalid access control adapter', Response::HTTP_INTERNAL_SERVER_ERROR));
        $this->runImbo([
            'database' => $this->createMock(DatabaseInterface::class),
            'storage' => $this->createMock(StorageInterface::class),
            'routes' => [],
            'trustedProxies' => [],
            'accessControl' => function () {
                return new stdClass();
            },
        ]);
    }

    /**
     * @covers ::run
     */
    public function testApplicationSetsTrustedProxies(): void
    {
        $this->expectOutputRegex('|^{.*}$|');

        $this->assertEmpty(Request::getTrustedProxies());
        $this->runImbo([
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
    public function testApplicationPassesRequestAndResponseToCallbacks(): void
    {
        // We just want to swallow the output, since we're testing it explicitly below.
        $this->expectOutputRegex('|.*}|');

        /** @var array */
        $default = require __DIR__ . '/../config/config.default.php';
        $test = [
            'database' => fn (Request $request, Response $response): DatabaseInterface&MockObject => $this->createMock(DatabaseInterface::class),
            'storage' => fn (Request $request, Response $response): StorageInterface&MockObject => $this->createMock(StorageInterface::class),
            'accessControl' => fn (Request $request, Response $response): AdapterInterface&MockObject => $this->createMock(AdapterInterface::class),
            'eventListeners' => [
                'test' => fn (Request $request, Response $response): TestListener => new TestListener(),
                'testSubelement' => [
                    'listener' => fn (Request $request, Response $response): TestListener => new TestListener(),
                ],
            ],
            'resources' => [
                'test' => fn (Request $request, Response $response): TestResource => new TestResource(),
            ],
        ];

        $this->runImbo(array_merge($default, $test));
    }

    /**
     * @covers ::run
     */
    public function testCanRunWithDefaultConfiguration(): void
    {
        $this->expectOutputRegex('|^{.*}$|');
        $this->runImbo($this->getDefaultConfig());
    }

    /**
     * @covers ::run
     */
    public function testThrowsExceptionIfTransformationsIsSetAndIsNotAnArray(): void
    {
        $config = $this->getDefaultConfig();
        $config['transformations'] = function (): void {
        };
        $this->expectExceptionObject(new InvalidArgumentException(
            'The "transformations" configuration key must be specified as an array',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        $this->runImbo($config);
    }

    private function getDefaultConfig(): array
    {
        /** @var array */
        $defaultConfig = require __DIR__ . '/../config/config.default.php';

        return array_replace_recursive(
            $defaultConfig,
            [
                'accessControl' => $this->createMock(AdapterInterface::class),
                'database' => $this->createMock(DatabaseInterface::class),
                'storage' => $this->createMock(StorageInterface::class),
            ],
        );
    }
}

class TestListener implements ListenerInterface
{
    public static function getSubscribedEvents(): array
    {
        return [];
    }
}

class TestResource implements ListenerInterface, ResourceInterface
{
    public static function getSubscribedEvents(): array
    {
        return [];
    }

    public function getAllowedMethods(): array
    {
        return [];
    }
}
