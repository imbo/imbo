<?php declare(strict_types=1);
namespace Imbo\EventManager;

use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Database\DatabaseInterface;
use Imbo\Storage\StorageInterface;
use Imbo\Auth\AccessControl\Adapter\AdapterInterface;
use Imbo\EventManager\EventManager;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Image\InputLoaderManager;
use Imbo\Image\OutputConverter\OutputConverterInterface;
use Imbo\Image\TransformationManager;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\EventManager\Event
 */
class EventTest extends TestCase {
    private $event;

    public function setUp() : void {
        $this->event = new Event();
    }

    public function getArguments() : array {
        return [
            'request' => [
                'getRequest', 'request', $this->createMock(Request::class),
            ],
            'response' => [
                'getResponse', 'response', $this->createMock(Response::class),
            ],
            'database' => [
                'getDatabase', 'database', $this->createMock(DatabaseInterface::class),
            ],
            'storage' => [
                'getStorage', 'storage', $this->createMock(StorageInterface::class),
            ],
            'accessControl' => [
                'getAccessControl', 'accessControl', $this->createMock(AdapterInterface::class),
            ],
            'manager' => [
                'getManager', 'manager', $this->createMock(EventManager::class),
            ],
            'transformationManager' => [
                'getTransformationManager', 'transformationManager', $this->createMock(TransformationManager::class),
            ],
            'inputLoaderManager' => [
                'getInputLoaderManager', 'inputLoaderManager', $this->createMock(InputLoaderManager::class),
            ],
            'outputConverterManager' => [
                'getOutputConverterManager', 'outputConverterManager', $this->createMock(OutputConverterInterface::class),
            ],
            'config' => [
                'getConfig', 'config', ['some' => 'config'],
            ],
            'handler' => [
                'getHandler', 'handler', 'handler name',
            ],
        ];
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
    public function testCanSetAndGetRequest(string $method, string $argument, $value) : void {
        $this->event->setArgument($argument, $value);
        $this->assertSame($value, $this->event->$method());
    }

    /**
     * @covers ::setName
     * @covers ::getName
     */
    public function testCanSetAndGetName() : void {
        $this->assertNull($this->event->getName());
        $this->assertSame($this->event, $this->event->setName('name'));
        $this->assertSame('name', $this->event->getName());
    }

    /**
     * @covers ::stopPropagation
     * @covers ::isPropagationStopped
     */
    public function testCanStopPropagation() : void {
        $this->assertFalse($this->event->isPropagationStopped());
        $this->assertSame($this->event, $this->event->stopPropagation());
        $this->assertTrue($this->event->isPropagationStopped());
    }

    /**
     * @covers ::getArgument
     */
    public function testThrowsExceptionWhenGettingArgumentThatDoesNotExist() : void {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Argument "foobar" does not exist',
            500
        ));
        $this->event->getArgument('foobar');
    }

    /**
     * @covers ::__construct
     * @covers ::setArguments
     */
    public function testCanSetArgumentsThroughConstructor() : void {
        $this->assertSame(
            'bar',
            (new Event(['foo' => 'bar']))->getArgument('foo')
        );
    }

    /**
     * @covers ::setArguments
     * @covers ::getArgument
     * @covers ::hasArgument
     */
    public function testSetArgumentsOverridesAllArguments() : void {
        $this->assertFalse($this->event->hasArgument('foo'));

        $this->assertSame($this->event, $this->event->setArguments(['foo' => 'bar']));
        $this->assertSame('bar', $this->event->getArgument('foo'));

        $this->assertSame($this->event, $this->event->setArguments(['bar' => 'foo']));
        $this->assertFalse($this->event->hasArgument('foo'));
        $this->assertSame('foo', $this->event->getArgument('bar'));
    }
}
