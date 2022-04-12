<?php declare(strict_types=1);
namespace Imbo\Model;

class Group implements ModelInterface
{
    /**
     * Name of the group
     */
    private ?string $name = null;

    /**
     * Resources
     *
     * @var array<string>
     */
    private array $resources = [];

    /**
     * Set the group name
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the group name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the group resources
     *
     * @param array<string> $resources
     */
    public function setResources(array $resources = []): self
    {
        $this->resources = $resources;
        return $this;
    }

    /**
     * Get the group resources
     *
     * @return array<string>
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * @return array{name:?string,resources:array<string>}
     */
    public function getData(): array
    {
        return [
            'name' => $this->getName(),
            'resources' => $this->getResources(),
        ];
    }
}
