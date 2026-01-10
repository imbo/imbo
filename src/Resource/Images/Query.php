<?php declare(strict_types=1);

namespace Imbo\Resource\Images;

use Imbo\Exception\RuntimeException;
use Imbo\Http\Response\Response;

class Query
{
    /**
     * The page to get.
     */
    private int $page = 1;

    /**
     * Number of images to get.
     */
    private int $limit = 20;

    /**
     * Return metadata or not.
     */
    private bool $returnMetadata = false;

    /**
     * Timestamp to start fetching from.
     */
    private ?int $from = null;

    /**
     * Timestamp to fetch to.
     */
    private ?int $to = null;

    /**
     * Image identifiers filter.
     *
     * @var array<string>
     */
    private array $imageIdentifiers = [];

    /**
     * Checksums filter.
     *
     * @var array<string>
     */
    private array $checksums = [];

    /**
     * Original checksums filter.
     *
     * @var array<string>
     */
    private array $originalChecksums = [];

    /**
     * Sort.
     *
     * @var array<int,array{field:string,sort:string}>
     */
    private $sort = [];

    /**
     * Set the page property.
     */
    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get the page.
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * Set the limit property.
     */
    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get the limit.
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Set the returnMetadata flag.
     *
     * @param bool $returnMetadata
     */
    public function setReturnMetadata($returnMetadata): self
    {
        $this->returnMetadata = $returnMetadata;

        return $this;
    }

    /**
     * Get the returnMetadata flag.
     */
    public function getReturnMetadata(): bool
    {
        return $this->returnMetadata;
    }

    /**
     * Set the from attribute.
     */
    public function setFrom(int $from): self
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Get the from attribute.
     */
    public function getFrom(): ?int
    {
        return $this->from;
    }

    /**
     * Set the to attribute.
     */
    public function setTo(int $to): self
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Get the to attribute.
     */
    public function getTo(): ?int
    {
        return $this->to;
    }

    /**
     * Set the imageIdentifiers filter.
     *
     * @param array<string> $imageIdentifiers
     */
    public function setImageIdentifiers(array $imageIdentifiers): self
    {
        $this->imageIdentifiers = $imageIdentifiers;

        return $this;
    }

    /**
     * Get the imageIdentifiers filter.
     *
     * @return array<string>
     */
    public function getImageIdentifiers(): array
    {
        return $this->imageIdentifiers;
    }

    /**
     * Set the checksums filter.
     *
     * @param array<string> $checksums
     */
    public function setChecksums(array $checksums): self
    {
        $this->checksums = $checksums;

        return $this;
    }

    /**
     * Get the checksums filter.
     *
     * @return array<string>
     */
    public function getChecksums(): array
    {
        return $this->checksums;
    }

    /**
     * Set the original checksums filter.
     *
     * @param array<string> $originalChecksums
     */
    public function setOriginalChecksums(array $originalChecksums): self
    {
        $this->originalChecksums = $originalChecksums;

        return $this;
    }

    /**
     * Get the original checksums filter.
     *
     * @return array<string>
     */
    public function getOriginalChecksums(): array
    {
        return $this->originalChecksums;
    }

    /**
     * Set the sort data.
     *
     * @param array<string> $sort
     */
    public function setSort(array $sort): self
    {
        $sortData = [];

        foreach ($sort as $field) {
            $field = trim($field);
            $dir = 'asc';

            if (empty($field)) {
                throw new RuntimeException('Badly formatted sort', Response::HTTP_BAD_REQUEST);
            }

            if (str_contains($field, ':')) {
                list($fieldName, $dir) = explode(':', $field);

                if ('asc' !== $dir && 'desc' !== $dir) {
                    throw new RuntimeException('Invalid sort value: '.$field, Response::HTTP_BAD_REQUEST);
                }

                $field = $fieldName;
            }

            $sortData[] = [
                'field' => $field,
                'sort' => $dir,
            ];
        }

        $this->sort = $sortData;

        return $this;
    }

    /**
     * Get the sort data.
     *
     * @return array<int,array{field:string,sort:string}>
     */
    public function getSort(): array
    {
        return $this->sort;
    }
}
