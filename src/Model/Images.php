<?php declare(strict_types=1);
namespace Imbo\Model;

class Images implements ModelInterface
{
    /**
     * An array of Image models
     *
     * @var array<Image>
     */
    private array $images = [];

    /**
     * Which fields to display
     *
     * @var array<string>
     */
    private array $fields = [];

    /**
     * Query hits
     */
    private int $hits = 0;

    /**
     * Limit the number of images
     */
    private int $limit = 20;

    /**
     * The page number
     */
    private int $page = 1;

    /**
     * Set the array of images
     *
     * @param array<Image> $images An array of Image models
     * @return self
     */
    public function setImages(array $images): self
    {
        $this->images = $images;
        return $this;
    }

    /**
     * Get the images
     *
     * @return array<Image>
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * Set the fields to display
     *
     * @param array<string>
     * @return self
     */
    public function setFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Get the fields to display
     *
     * @return array<string>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get the number of images
     */
    public function getCount(): int
    {
        return count($this->images);
    }

    /**
     * Set the hits property
     */
    public function setHits(int $hits): self
    {
        $this->hits = $hits;
        return $this;
    }

    /**
     * Get the hits property
     */
    public function getHits(): int
    {
        return $this->hits;
    }

    /**
     * Set the limit
     */
    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Get the limit
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Set the page
     */
    public function setPage(int $page): self
    {
        $this->page = $page;
        return $this;
    }

    /**
     * Get the page
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @return array{images:array<Image>,fields:array<string>,count:int,hits:int,limit:int,page:int}
     */
    public function getData()
    {
        return [
            'images' => $this->getImages(),
            'fields' => $this->getFields(),
            'count' => $this->getCount(),
            'hits' => $this->getHits(),
            'limit' => $this->getLimit(),
            'page' => $this->getPage(),
        ];
    }
}
