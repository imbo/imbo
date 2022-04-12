<?php declare(strict_types=1);
namespace Imbo\Auth\AccessControl;

/**
 * Abstract query interface for access control
 */
abstract class AbstractQuery
{
    private int $limit = 20;
    private int $page = 1;

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;
        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }
}
