<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Resource\Images;

use Imbo\Exception\RuntimeException;

/**
 * Query object for the images resource
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources\Images
 */
class Query {
    /**
     * The page to get
     *
     * @var int
     */
    private $page = 1;

    /**
     * Number of images to get
     *
     * @var int
     */
    private $limit = 20;

    /**
     * Return metadata or not
     *
     * @var boolean
     */
    private $returnMetadata = false;

    /**
     * Timestamp to start fetching from
     *
     * @var int
     */
    private $from;

    /**
     * Timestamp to fetch to
     *
     * @var int
     */
    private $to;

    /**
     * Image identifiers filter
     *
     * @var array
     */
    private $imageIdentifiers = [];

    /**
     * Checksums filter
     *
     * @var array
     */
    private $checksums = [];

    /**
     * Original checksums filter
     *
     * @var array
     */
    private $originalChecksums = [];

    /**
     * Sort
     *
     * @var array
     */
    private $sort = [];

    /**
     * Set or get the page property
     *
     * @param int $page Give this a value to set the page property
     * @return int|self
     */
    public function page($page = null) {
        if ($page === null) {
            return $this->page;
        }

        $this->page = (int) $page;

        return $this;
    }

    /**
     * Set or get the limit property
     *
     * @param int $limit Give this a value to set the limit property
     * @return int|self
     */
    public function limit($limit = null) {
        if ($limit === null) {
            return $this->limit;
        }

        $this->limit = (int) $limit;

        return $this;
    }

    /**
     * Set or get the returnMetadata flag
     *
     * @param boolean $returnMetadata Give this a value to set the returnMetadata flag
     * @return boolean|self
     */
    public function returnMetadata($returnMetadata = null) {
        if ($returnMetadata === null) {
            return $this->returnMetadata;
        }

        $this->returnMetadata = (bool) $returnMetadata;

        return $this;
    }

    /**
     * Set or get the from attribute
     *
     * @param int $from Give this a value to set the from property
     * @return int|self
     */
    public function from($from = null) {
        if ($from === null) {
            return $this->from;
        }

        $this->from = (int) $from;

        return $this;
    }

    /**
     * Set or get the to attribute
     *
     * @param int $to Give this a value to set the to property
     * @return int|self
     */
    public function to($to = null) {
        if ($to === null) {
            return $this->to;
        }

        $this->to = (int) $to;

        return $this;
    }

    /**
     * Set or get the imageIdentifiers filter
     *
     * @param array $imageIdentifiers Give this a value to set the property
     * @return array|self
     */
    public function imageIdentifiers(array $imageIdentifiers = null) {
        if ($imageIdentifiers === null) {
            return $this->imageIdentifiers;
        }

        $this->imageIdentifiers = $imageIdentifiers;

        return $this;
    }

    /**
     * Set or get the checksums filter
     *
     * @param array $checksums Give this a value to set the property
     * @return array|self
     */
    public function checksums(array $checksums = null) {
        if ($checksums === null) {
            return $this->checksums;
        }

        $this->checksums = $checksums;

        return $this;
    }

    /**
     * Set or get the original checksums filter
     *
     * @param array $checksums Give this a value to set the property
     * @return array|self
     */
    public function originalChecksums(array $checksums = null) {
        if ($checksums === null) {
            return $this->originalChecksums;
        }

        $this->originalChecksums = $checksums;

        return $this;
    }

    /**
     * Set or get the sort data
     *
     * @param array $sort Specify a value to set the sort property
     * @return array|self
     */
    public function sort(array $sort = null) {
        if ($sort === null) {
            return $this->sort;
        }

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
                'sort' => $dir,
            ];
        }

        $this->sort = $sortData;

        return $this;
    }
}
