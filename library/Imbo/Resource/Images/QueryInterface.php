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
 * @package Interfaces
 * @subpackage ImagesQuery
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Resource\Images;

/**
 * Query interface
 *
 * @package Interfaces
 * @subpackage ImagesQuery
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
interface QueryInterface {
    /**
     * Set or get the page property
     *
     * @param int $page Give this a value to set the page property
     * @return int|QueryInterface
     */
    function page($page = null);

    /**
     * Set or get the limit property
     *
     * @param int $limit Give this a value to set the limit property
     * @return int|QueryInterface
     */
    function limit($limit = null);

    /**
     * Set or get the returnMetadata flag
     *
     * @param boolean $returnMetadata Give this a value to set the returnMetadata flag
     * @return boolean|QueryInterface
     */
    function returnMetadata($returnMetadata = null);

    /**
     * Set or get the metadataQuery property
     *
     * @param array $metadataQuery Give this a value to set the property
     * @return array|QueryInterface
     */
    function metadataQuery(array $metadataQuery = null);

    /**
     * Set or get the from attribute
     *
     * @param int $from Give this a value to set the from property
     * @return int|QueryInterface
     */
    function from($from = null);

    /**
     * Set or get the to attribute
     *
     * @param int $to Give this a value to set the to property
     * @return int|QueryInterface
     */
    function to($to = null);
}
