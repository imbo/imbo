<?php declare(strict_types=1);

namespace Imbo\Model;

use function count;

class Groups implements ModelInterface
{
    /**
     * An array of groups.
     *
     * @var array<string,mixed>
     */
    private array $groups = [];

    /**
     * Query hits.
     */
    private ?int $hits = null;

    /**
     * Limit the number of groups.
     */
    private ?int $limit = null;

    /**
     * The page number.
     */
    private ?int $page = null;

    /**
     * Set the array of groups.
     *
     * @param array<string,mixed> $groups An array of groups
     */
    public function setGroups(array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * Get the groups.
     *
     * @return array<string,mixed>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Get the number of groups.
     */
    public function getCount(): int
    {
        return count($this->groups);
    }

    /**
     * Set the hits property.
     */
    public function setHits(int $hits): self
    {
        $this->hits = $hits;

        return $this;
    }

    /**
     * Get the hits property.
     */
    public function getHits(): ?int
    {
        return $this->hits;
    }

    /**
     * Set the limit.
     */
    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get the limit.
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * Set the page.
     */
    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get the page.
     */
    public function getPage(): ?int
    {
        return $this->page;
    }

    /**
     * @return array{groups:array<string,mixed>,count:int,hits:?int,limit:?int,page:?int}
     */
    public function getData(): array
    {
        return [
            'groups' => $this->getGroups(),
            'count' => $this->getCount(),
            'hits' => $this->getHits(),
            'limit' => $this->getLimit(),
            'page' => $this->getPage(),
        ];
    }
}
