<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
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
 * @package Resources
 * @subpackage ImagesQuery
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Resource\Images;

/**
 * Query object for the images resource
 *
 * @package Resources
 * @subpackage ImagesQuery
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Query implements QueryInterface {
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
     * Metadata query
     *
     * @var array
     */
    private $metadataQuery = array();

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
     * {@inheritdoc}
     */
    public function page($page = null) {
        if ($page === null) {
            return $this->page;
        }

        $this->page = (int) $page;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function limit($limit = null) {
        if ($limit === null) {
            return $this->limit;
        }

        $this->limit = (int) $limit;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function returnMetadata($returnMetadata = null) {
        if ($returnMetadata === null) {
            return $this->returnMetadata;
        }

        $this->returnMetadata = (bool) $returnMetadata;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function metadataQuery(array $metadataQuery = null) {
        if ($metadataQuery === null) {
            return $this->metadataQuery;
        }

        $this->metadataQuery = $metadataQuery;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function from($from = null) {
        if ($from === null) {
            return $this->from;
        }

        $this->from = (int) $from;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function to($to = null) {
        if ($to === null) {
            return $this->to;
        }

        $this->to = (int) $to;

        return $this;
    }
}
