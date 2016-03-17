<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Model;

/**
 * Groups model
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Models
 */
class Groups implements ModelInterface {
    /**
     * An array of groups
     *
     * @var array
     */
    private $groups = [];

    /**
     * Query hits
     *
     * @var int
     */
    private $hits;

    /**
     * Limit the number of groups
     *
     * @var int
     */
    private $limit;

    /**
     * The page number
     *
     * @var int
     */
    private $page;

    /**
     * Set the array of groups
     *
     * @param array $groups An array of groups
     * @return Groups
     */
    public function setGroups(array $groups) {
        $this->groups = $groups;

        return $this;
    }

    /**
     * Get the groups
     *
     * @return array
     */
    public function getGroups() {
        return $this->groups;
    }

    /**
     * Get the number of groups
     *
     * @return int
     */
    public function getCount() {
        return count($this->groups);
    }

    /**
     * Set the hits property
     *
     * @param int $hits The amount of query hits
     * @return self
     */
    public function setHits($hits) {
        $this->hits = (int) $hits;

        return $this;
    }

    /**
     * Get the hits property
     *
     * @return int
     */
    public function getHits() {
        return $this->hits;
    }

    /**
     * Set the limit
     *
     * @param int $limit The limit
     * @return self
     */
    public function setLimit($limit) {
        $this->limit = (int) $limit;

        return $this;
    }

    /**
     * Get the limit
     *
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * Set the page
     *
     * @param int $page The page
     * @return self
     */
    public function setPage($page) {
        $this->page = (int) $page;

        return $this;
    }

    /**
     * Get the page
     *
     * @return int
     */
    public function getPage() {
        return $this->page;
    }

    /**
     * {@inheritdoc}
     */
    public function getData() {
        return [
            'groups' => $this->getGroups(),
            'count' => $this->getCount(),
            'hits' => $this->getHits(),
            'limit' => $this->getLimit(),
            'page' => $this->getPage(),
        ];
    }
}
