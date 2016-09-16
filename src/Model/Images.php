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
 * Images model
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
class Images implements ModelInterface {
    /**
     * An array of Image models
     *
     * @var Image[]
     */
    private $images = [];

    /**
     * Which fields to display
     *
     * @var string[]
     */
    private $fields = [];

    /**
     * Query hits
     *
     * @var int
     */
    private $hits;

    /**
     * Limit the number of images
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
     * Set the array of images
     *
     * @param Image[] $images An array of Image models
     * @return Images
     */
    public function setImages(array $images) {
        $this->images = $images;

        return $this;
    }

    /**
     * Get the images
     *
     * @return Image[]
     */
    public function getImages() {
        return $this->images;
    }

    /**
     * Set the fields to display
     *
     * @param string[]
     * @return self
     */
    public function setFields(array $fields) {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Get the fields to display
     *
     * @return string[]
     */
    public function getFields() {
        return $this->fields;
    }

    /**
     * Get the number of images
     *
     * @return int
     */
    public function getCount() {
        return count($this->images);
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
            'images' => $this->getImages(),
            'fields' => $this->getFields(),
            'count' => $this->getCount(),
            'hits' => $this->getHits(),
            'limit' => $this->getLimit(),
            'page' => $this->getPage(),
        ];
    }
}
