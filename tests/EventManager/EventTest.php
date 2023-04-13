<?php declare(strict_types=1);
namespace Imbo\EventManager;

use Closure;
use Imbo\Auth\AccessControl\Adapter\AdapterInterface;
use Imbo\Database\DatabaseInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Image\InputLoaderManager;
use Imbo\Image\OutputConverterManager;
use Imbo\Image\TransformationManager;
use Imbo\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\EventManager\Event
 */
class EventTest extends TestCase
{
    private Event $event;

    public function setUp(): void
    {
        $this->event = new Event();
    }

    /**
     * @dataProvider getArguments
     * @covers ::setArgument
     * @covers ::getRequest
     * @covers ::getResponse
     * @covers ::getDatabase
     * @covers ::getStorage
     * @covers ::getAccessControl
     * @covers ::getManager
     * @covers ::getConfig
     * @covers ::getHandler
     * @covers ::getTransformationManager
     * @covers ::getInputLoaderManager
     * @covers ::getOutputConverterManager
     */
    public function testCanSetAndGetRequest(string $method, string $argument, mixed $value): void
    {
        if ($value instanceof Closure) {
            /** @var Closure */
            $new = $value->bindTo($this);
            /** @var mixed */
            $value = $new();
        }

        $this->event->setArgument($argument, $value);
        $this->assertSame($value, $this->event->$method());
    }

    /**
     * @covers ::setName
     * @covers ::getName
     */
    public function testCanSetAndGetName(): void
    {
        $this->assertNull($this->event->getName());
        $this->assertSame($this->event, $this->event->setName('name'));
        $this->assertSame('name', $this->event->getName());
    }

    /**
     * @covers ::stopPropagation
     * @covers ::isPropagationStopped
     */
    public function testCanStopPropagation(): void
    {
        $this->assertFalse($this->event->isPropagationStopped());
        $this->assertSame($this->event, $this->event->stopPropagation());
        $this->assertTrue($this->event->isPropagationStopped());
    }

    /**
     * @covers ::getArgument
     */
    public function testThrowsExceptionWhenGettingArgumentThatDoesNotExist(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Argument "foobar" does not exist',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        $this->event->getArgument('foobar');
    }

    /**
     * @covers ::__construct
     * @covers ::setArguments
     */
    public function testCanSetArgumentsThroughConstructor(): void
    {
        $this->assertSame(
            'bar',
            (new Event(['foo' => 'bar']))->getArgument('foo'),
        );
    }

    /**
     * @covers ::setArguments
     * @covers ::getArgument
     * @covers ::hasArgument
     */
    public function testSetArgumentsOverridesAllArguments(): void
    {
        $this->assertFalse($this->event->hasArgument('foo'));

        $this->assertSame($this->event, $this->event->setArguments(['foo' => 'bar']));
        $this->assertSame('bar', $this->event->getArgument('foo'));

        $this->assertSame($this->event, $this->event->setArguments(['bar' => 'foo']));
        $this->assertFalse($this->event->hasArgument('foo'));
        $this->assertSame('foo', $this->event->getArgument('bar'));
    }

    /**
     * @return array<array{method:string,argument:string,value:mixed}>
     */
    public static function getArguments(): array
    {
        $mockCreator =
            /** @param class-string $className */
            fn (string $className): Closure =>
                fn () => $this->createMock($className);

        return [
            'request' => [
                'method' => 'getRequest',
                'argument' => 'request',
                'value' => $mockCreator(Request::class),
            ],
            'response' => [
                'method' => 'getResponse',
                'argument' => 'response',
                'value' => $mockCreator(Response::class),
            ],
            'database' => [
                'method' => 'getDatabase',
                'argument' => 'database',
                'value' => $mockCreator(DatabaseInterface::class),
            ],
            'storage' => [
                'method' => 'getStorage',
                'argument' => 'storage',
                'value' => $mockCreator(StorageInterface::class),
            ],
            'accessControl' => [
                'method' => 'getAccessControl',
                'argument' => 'accessControl',
                'value' => $mockCreator(AdapterInterface::class),
            ],
            'manager' => [
                'method' => 'getManager',
                'argument' => 'manager',
                'value' => $mockCreator(EventManager::class),
            ],
            'transformationManager' => [
                'method' => 'getTransformationManager',
                'argument' => 'transformationManager',
                'value' => $mockCreator(TransformationManager::class),
            ],
            'inputLoaderManager' => [
                'method' => 'getInputLoaderManager',
                'argument' => 'inputLoaderManager',
                'value' => $mockCreator(InputLoaderManager::class),
            ],
            'outputConverterManager' => [
                'method' => 'getOutputConverterManager',
                'argument' => 'outputConverterManager',
                'value' => $mockCreator(OutputConverterManager::class),
            ],
            'config' => [
                'method' => 'getConfig',
                'argument' => 'config',
                'value' => ['some' => 'config'],
            ],
            'handler' => [
                'method' => 'getHandler',
                'argument' => 'handler',
                'value' => 'handler name',
            ],
        ];
    }
}
