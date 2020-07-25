<?php declare(strict_types=1);
namespace Imbo\Resource\Images;

use Imbo\Exception\RuntimeException;

class Query {
    /**
     * The page to get
     */
    private int $page = 1;

    /**
     * Number of images to get
     */
    private int $limit = 20;

    /**
     * Return metadata or not
     */
    private bool $returnMetadata = false;

    /**
     * Timestamp to start fetching from
     */
    private ?int $from = null;

    /**
     * Timestamp to fetch to
     */
    private ?int $to = null;

    /**
     * Image identifiers filter
     *
     * @var string[]
     */
    private array $imageIdentifiers = [];

    /**
     * Checksums filter
     *
     * @var string[]
     */
    private array $checksums = [];

    /**
     * Original checksums filter
     *
     * @var string[]
     */
    private array $originalChecksums = [];

    /**
     * Sort
     *
     * @var array<int, array{field: string, sort: string}>
     */
    private $sort = [];

    /**
     * Set the page property
     *
     * @param int $page
     * @return self
     */
    public function setPage(int $page) : self {
        $this->page = $page;

        return $this;
    }

    /**
     * Get the page
     *
     * @return int
     */
    public function getPage() : int {
        return $this->page;
    }

    /**
     * Set the limit property
     *
     * @param int $limit
     * @return self
     */
    public function setLimit(int $limit) : self {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get the limit
     *
     * @return int
     */
    public function getLimit() : int {
        return $this->limit;
    }

    /**
     * Set the returnMetadata flag
     *
     * @param bool $returnMetadata
     * @return self
     */
    public function setReturnMetadata($returnMetadata) : self {
        $this->returnMetadata = $returnMetadata;

        return $this;
    }

    /**
     * Get the returnMetadata flag
     *
     * @return bool
     */
    public function getReturnMetadata() : bool {
        return $this->returnMetadata;
    }

    /**
     * Set the from attribute
     *
     * @param int $from
     * @return self
     */
    public function setFrom(int $from) : self {
        $this->from = $from;

        return $this;
    }

    /**
     * Get the from attribute
     *
     * @return ?int
     */
    public function getFrom() : ?int {
        return $this->from;
    }

    /**
     * Set the to attribute
     *
     * @param int $to
     * @return self
     */
    public function setTo(int $to) : self {
        $this->to = $to;

        return $this;
    }

    /**
     * Get the to attribute
     *
     * @return ?int
     */
    public function getTo() : ?int {
        return $this->to;
    }

    /**
     * Set the imageIdentifiers filter
     *
     * @param string[] $imageIdentifiers
     * @return self
     */
    public function setImageIdentifiers(array $imageIdentifiers) : self {
        $this->imageIdentifiers = $imageIdentifiers;

        return $this;
    }

    /**
     * Get the imageIdentifiers filter
     *
     * @return string[]
     */
    public function getImageIdentifiers() : array {
        return $this->imageIdentifiers;
    }

    /**
     * Set the checksums filter
     *
     * @param string[] $checksums
     * @return self
     */
    public function setChecksums(array $checksums) : self {
        $this->checksums = $checksums;

        return $this;
    }

    /**
     * Get the checksums filter
     *
     * @return string[]
     */
    public function getChecksums() : array {
        return $this->checksums;
    }

    /**
     * Set the original checksums filter
     *
     * @param string[] $originalChecksums
     * @return self
     */
    public function setOriginalChecksums(array $originalChecksums) : self {
        $this->originalChecksums = $originalChecksums;

        return $this;
    }

    /**
     * Get the original checksums filter
     *
     * @return string[]
     */
    public function getOriginalChecksums() : array {
        return $this->originalChecksums;
    }

    /**
     * Set the sort data
     *
     * @param string[] $sort
     * @return self
     */
    public function setSort(array $sort) : self {
        $sortData = [];

        foreach ($sort as $field) {
            $field = trim($field);
            $dir = 'asc';

            if (empty($field)) {
                throw new RuntimeException('Badly formatted sort', 400);
            }

            if (strpos($field, ':') !== false) {
                list($fieldName, $dir) = explode(':', $field);

                if ($dir !== 'asc' && $dir !== 'desc') {
                    throw new RuntimeException('Invalid sort value: ' . $field, 400);
                }

                $field = $fieldName;
            }

            $sortData[] = [
                'field' => $field,
                'sort'  => $dir,
            ];
        }

        $this->sort = $sortData;

        return $this;
    }

    /**
     * Get the sort data
     *
     * @return array<int, array{field: string, sort: string}>
     */
    public function getSort() : array {
        return $this->sort;
    }
}
