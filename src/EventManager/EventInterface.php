<?php declare(strict_types=1);
namespace Imbo\EventManager;

use Imbo\Auth\AccessControl\Adapter\AdapterInterface;
use Imbo\Database\DatabaseInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Image\InputLoaderManager;
use Imbo\Image\OutputConverterManager;
use Imbo\Image\TransformationManager;
use Imbo\Storage\StorageInterface;

interface EventInterface
{
    public function getName(): ?string;
    public function setName(string $name): self;
    public function isPropagationStopped(): bool;
    public function stopPropagation(): self;

    /**
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function getArgument(string $key);

    /**
     * @param mixed $value
     */
    public function setArgument(string $key, $value): self;

    /**
     * @param array $arguments
     */
    public function setArguments(array $arguments = []): self;
    public function hasArgument(string $key): bool;
    public function getRequest(): Request;
    public function getResponse(): Response;
    public function getDatabase(): DatabaseInterface;
    public function getStorage(): StorageInterface;
    public function getAccessControl(): AdapterInterface;
    public function getManager(): EventManager;
    public function getTransformationManager(): TransformationManager;
    public function getOutputConverterManager(): OutputConverterManager;
    public function getInputLoaderManager(): InputLoaderManager;
    public function getConfig(): array;
    public function getHandler(): string;
}
