<?php declare(strict_types=1);
namespace Imbo\EventManager;

use Imbo\Auth\AccessControl\Adapter\AdapterInterface;
use Imbo\Database\DatabaseInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Image\InputLoaderManager;
use Imbo\Image\OutputConverterManager;
use Imbo\Image\TransformationManager;
use Imbo\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Event::class)]
class EventTest extends TestCase
{
    private Event $event;

    public function setUp(): void
    {
        $this->event = new Event();
    }

    #[DataProvider('getArguments')]
    public function testCanSetAndGetRequest(string $method, string $argument, mixed $value): void
    {
        if (is_string($value) && (class_exists($value) || interface_exists($value))) {
            $value = $this->createStub($value);
        }

        $this->event->setArgument($argument, $value);
        $this->assertSame($value, $this->event->$method());
    }

    public function testCanSetAndGetName(): void
    {
        $this->assertNull($this->event->getName());
        $this->assertSame($this->event, $this->event->setName('name'));
        $this->assertSame('name', $this->event->getName());
    }

    public function testCanStopPropagation(): void
    {
        $this->assertFalse($this->event->isPropagationStopped());
        $this->assertSame($this->event, $this->event->stopPropagation());
        $this->assertTrue($this->event->isPropagationStopped());
    }

    public function testThrowsExceptionWhenGettingArgumentThatDoesNotExist(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Argument "foobar" does not exist',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        $this->event->getArgument('foobar');
    }

    public function testCanSetArgumentsThroughConstructor(): void
    {
        $this->assertSame(
            'bar',
            (new Event(['foo' => 'bar']))->getArgument('foo'),
        );
    }

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
        return [
            'request' => [
                'method' => 'getRequest',
                'argument' => 'request',
                'value' => Request::class,
            ],
            'response' => [
                'method' => 'getResponse',
                'argument' => 'response',
                'value' => Response::class,
            ],
            'database' => [
                'method' => 'getDatabase',
                'argument' => 'database',
                'value' => DatabaseInterface::class,
            ],
            'storage' => [
                'method' => 'getStorage',
                'argument' => 'storage',
                'value' => StorageInterface::class,
            ],
            'accessControl' => [
                'method' => 'getAccessControl',
                'argument' => 'accessControl',
                'value' => AdapterInterface::class,
            ],
            'manager' => [
                'method' => 'getManager',
                'argument' => 'manager',
                'value' => EventManager::class,
            ],
            'transformationManager' => [
                'method' => 'getTransformationManager',
                'argument' => 'transformationManager',
                'value' => TransformationManager::class,
            ],
            'inputLoaderManager' => [
                'method' => 'getInputLoaderManager',
                'argument' => 'inputLoaderManager',
                'value' => InputLoaderManager::class,
            ],
            'outputConverterManager' => [
                'method' => 'getOutputConverterManager',
                'argument' => 'outputConverterManager',
                'value' => OutputConverterManager::class,
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
