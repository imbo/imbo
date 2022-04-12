<?php declare(strict_types=1);
namespace Imbo\Model;

class AccessRule implements ModelInterface
{
    /**
     * ID of the rule
     */
    private ?int $id = null;

    /**
     * Group name
     */
    private ?string $group = null;

    /**
     * List of resources
     *
     * @var array<string>
     */
    private array $resources = [];

    /**
     * List of users
     *
     * @var array<string>
     */
    private array $users = [];

    /**
     * Set the ID
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get the ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the group
     */
    public function setGroup(string $group): self
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Get the group
     */
    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * Set the resources
     *
     * @param array<string> $resources
     */
    public function setResources(array $resources): self
    {
        $this->resources = $resources;
        return $this;
    }

    /**
     * Get the resources
     *
     * @return array<string>
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * Set the users
     *
     * @param array<string> $users
     */
    public function setUsers(array $users): self
    {
        $this->users = $users;
        return $this;
    }

    /**
     * Get the users
     *
     * @return array<string>
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @return array{id:string,group:string,resources:array<string>,users:array<string>}
     */
    public function getData(): array
    {
        return [
            'id' => $this->getId(),
            'group' => $this->getGroup(),
            'resources' => $this->getResources(),
            'users' => $this->getUsers(),
        ];
    }
}
