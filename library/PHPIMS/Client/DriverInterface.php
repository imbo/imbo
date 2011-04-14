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
 * @subpackage Client
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Client;

/**
 * Client driver interface
 *
 * This is an interface for different client drivers.
 *
 * @package PHPIMS
 * @subpackage Client
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
interface DriverInterface {
    /**
     * POST some data to an URL
     *
     * @param string $url The URL to POST to
     * @param array $metadata The metadata to POST. This array will be json_encoded and sent to the
     *                        server as $_POST['metadata']
     * @param string $filePath Optional path to a file we want to add to the POST
     * @return PHPIMS\Client\Response
     * @throws PHPIMS\Client\Driver\Exception
     */
    public function post($url, array $metadata = null, $filePath = null);

    /**
     * Perform a GET to $url
     *
     * @param string $url The URL to GET
     * @return PHPIMS\Client\Response
     * @throws PHPIMS\Client\Driver\Exception
     */
    public function get($url);

    /**
     * Perform a HEAD to $url
     *
     * @param string $url The URL to HEAD
     * @return PHPIMS\Client\Response
     * @throws PHPIMS\Client\Driver\Exception
     */
    public function head($url);

    /**
     * Perform a DELETE request to $url
     *
     * @param string $url The URL to DELETE
     * @return PHPIMS\Client\Response
     * @throws PHPIMS\Client\Driver\Exception
     */
    public function delete($url);

    /**
     * Add an image
     *
     * @param string $path The path to the image to add
     * @param string $url The URL to push data to
     * @param array $metadata Metadata to add along with the image
     * @return PHPIMS\Client\Response
     * @throws PHPIMS\Client\Driver\Exception
     */
    public function addImage($path, $url, array $metadata = null);
}