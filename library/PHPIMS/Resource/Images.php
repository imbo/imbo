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
 * @subpackage Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Resource;

use PHPIMS\Request\RequestInterface;
use PHPIMS\Response\ResponseInterface;
use PHPIMS\Database\DatabaseInterface;
use PHPIMS\Storage\StorageInterface;
use PHPIMS\Resource\Images\Query;
use PHPIMS\Database\Exception as DatabaseException;

/**
 * Images resource
 *
 * This resource will let users fetch images based on queries. The following query parameters can
 * be used:
 *
 * page     => Page number. Defaults to 1
 * limit    => Limit to a number of images pr. page. Defaults to 20
 * metadata => Wether or not to include metadata pr. image. Set to 1 to enable
 * query    => urlencoded json data to use in the query
 * from     => Unix timestamp to fetch from
 * to       => Unit timestamp to fetch to
 *
 * @package PHPIMS
 * @subpackage Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class Images extends Resource implements ResourceInterface {
    /**
     * @see PHPIMS\Resource\ResourceInterface::getAllowedMethods()
     */
    public function getAllowedMethods() {
        return array(
            RequestInterface::METHOD_GET,
        );
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::get()
     */
    public function get(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $query = new Query();

        if ($request->has('page')) {
            $query->page($request->get('page'));
        }

        if ($request->has('num')) {
            $query->num($request->get('num'));
        }

        if ($request->has('metadata')) {
            $query->returnMetadata($request->get('metadata'));
        }

        if ($request->has('from')) {
            $query->from($request->get('from'));
        }

        if ($request->has('to')) {
            $query->to($request->get('to'));
        }

        if ($request->has('query')) {
            $data = json_decode($request->get('query'), true);

            if (is_array($data)) {
                $query->query($data);
            }
        }

        try {
            $images = $database->getImages($request->getPublicKey(), $query);
        } catch (DatabaseException $e) {
            throw new Exception('Database error: ' . $e->getMessage(), $e->getCode(), $e);
        }

        $response->setBody($images);
    }
}
