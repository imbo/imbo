<?php
/**
 * PHPIMS
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package PHPIMS
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

/**
 * Metadata block
 *
 * An instance of this class represents a single metadata block that usually is a part of a larger
 * collection.
 *
 * @package PHPIMS
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_Image_Metadata {
    /**
     * Metadata key
     *
     * @var string
     */
    protected $key = null;

    /**
     * Metadata value
     *
     * @var string
     */
    protected $value = null;

    /**
     * The collcetion this block is a part of
     *
     * @var PHPIMS_Image_Metadata_Collection
     */
    protected $collection = null;

    /**
     * The image this block belongs to
     *
     * @var PHPIMS_Image
     */
    protected $image = null;

    /**
     * Class constructor
     *
     * @param string $key The key to the block
     * @param string $value The value
     * @param PHPIMS_Image_Metadata_Collection $collection The collection this block is a part of
     * @param PHPIMS_Image $image The image this block belongs to
     */
    public function __construct($key = null, $value = null, $collection = null, $image = null) {
        if ($key !== null) {
            $this->setKey($key);
        }

        if ($value !== null) {
            $this->setValue($value);
        }

        if ($collection !== null) {
            $this->setCollection($collection);
        }

        if ($image !== null) {
            $this->setImage($image);
        }
    }

    /**
     * Get the key
     *
     * @return string
     */
    public function getKey() {
        return $this->string;
    }

    /**
     * Set the key
     *
     * @param string $key The key to set
     * @return PHPIMS_Image_Metadata
     */
    public function setKey($key) {
        $this->key = $key;

        return $this;
    }

    /**
     * Get the value
     *
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * Set the value
     *
     * @param string $value The value to set
     * @return PHPIMS_Image_Metadata
     */
    public function setValue($value) {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the collection
     *
     * @return PHPIMS_Image_Metadata_Collection
     */
    public function getCollection() {
        return $this->collection;
    }

    /**
     * Set the collection
     *
     * @param PHPIMS_Image_Metadata_Collection $collection The collection to set
     * @return PHPIMS_Image
     */
    public function setCollection(PHPIMS_Image_Metadata_Collection $collection) {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Get the image
     *
     * @return PHPIMS_Image
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Set the image
     *
     * @param PHPIMS_Image $image The image to set
     * @return PHPIMS_Image
     */
    public function setImage(PHPIMS_Image $image) {
        $this->image = $image;

        return $this;
    }
}