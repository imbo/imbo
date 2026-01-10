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

use function sprintf;

class Event implements EventInterface
{
    private ?string $name = null;
    private bool $propagationStopped = false;
    private array $arguments = [];

    public function __construct(array $arguments = [])
    {
        $this->arguments = $arguments;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): self
    {
        $this->propagationStopped = true;

        return $this;
    }

    public function getArgument(string $key)
    {
        if ($this->hasArgument($key)) {
            return $this->arguments[$key];
        }

        throw new InvalidArgumentException(sprintf('Argument "%s" does not exist', $key), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function setArgument(string $key, $value): self
    {
        $this->arguments[$key] = $value;

        return $this;
    }

    public function setArguments(array $arguments = []): self
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function hasArgument(string $key): bool
    {
        return isset($this->arguments[$key]);
    }

    public function getRequest(): Request
    {
        return $this->getArgument('request');
    }

    public function getResponse(): Response
    {
        return $this->getArgument('response');
    }

    public function getDatabase(): DatabaseInterface
    {
        return $this->getArgument('database');
    }

    public function getStorage(): StorageInterface
    {
        return $this->getArgument('storage');
    }

    public function getAccessControl(): AdapterInterface
    {
        return $this->getArgument('accessControl');
    }

    public function getManager(): EventManager
    {
        return $this->getArgument('manager');
    }

    public function getTransformationManager(): TransformationManager
    {
        return $this->getArgument('transformationManager');
    }

    public function getInputLoaderManager(): InputLoaderManager
    {
        return $this->getArgument('inputLoaderManager');
    }

    public function getOutputConverterManager(): OutputConverterManager
    {
        return $this->getArgument('outputConverterManager');
    }

    public function getConfig(): array
    {
        return $this->getArgument('config');
    }

    public function getHandler(): string
    {
        return $this->getArgument('handler');
    }
}
