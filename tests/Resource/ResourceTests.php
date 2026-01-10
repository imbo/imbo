<?php declare(strict_types=1);

namespace Imbo\Resource;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

use function sprintf;

abstract class ResourceTests extends TestCase
{
    /**
     * Return a resource that can be tested.
     */
    abstract protected function getNewResource(): ResourceInterface;

    #[AllowMockObjectsWithoutExpectations]
    public function testReturnsCorrectEventSubscriptions(): void
    {
        $this->assertIsArray($this->getNewResource()::getSubscribedEvents());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testReturnsTheCorrectAllowedMethods(): void
    {
        $this->expectNotToPerformAssertions();

        $resource = $this->getNewResource();

        // Translate the class name to an event name: Imbo\Resource\GlobalShortUrl => globalshorturl
        $shortName = strtolower(substr($resource::class, (int) strrpos($resource::class, '\\') + 1));

        $methods = $resource->getAllowedMethods();
        $definition = $resource::getSubscribedEvents();

        foreach ($methods as $method) {
            $expectedEventName = strtolower($shortName.'.'.$method);

            foreach (array_keys($definition) as $event) {
                if ($event === $expectedEventName) {
                    continue 2;
                }
            }

            $this->fail(sprintf(
                'Resource allows %s, but no listener definition subscribes to %s',
                $method,
                $expectedEventName,
            ));
        }

        foreach (array_keys($definition) as $event) {
            if (!str_starts_with($event, $shortName)) {
                continue;
            }

            $expectedMethod = strtoupper(substr($event, (int) strrpos($event, '.') + 1));

            foreach ($methods as $method) {
                if ($method === $expectedMethod) {
                    continue 2;
                }
            }

            $this->fail(sprintf(
                'Resource subscribes to %s but does not allow %s',
                $event,
                $expectedMethod,
            ));
        }
    }
}
