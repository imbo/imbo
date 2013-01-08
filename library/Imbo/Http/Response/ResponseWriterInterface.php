<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http\Response;

use Imbo\Http\Request\RequestInterface;

/**
 * Response writer interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
 */
interface ResponseWriterInterface {
    /**
     * Return a formatted message using a chosen formatter based on the request
     *
     * @param array $data Data to write in another format
     * @param RequestInterface $request A request instance
     * @param ResponseInterface $response A response instance
     * @param boolean $strict Whether or not the response writer will throw a RuntimeException with
     *                        status code 406 (Not Acceptable) if it can not produce acceptable
     *                        content for the user agent.
     * @throws RuntimeException
     */
    function write(array $data, RequestInterface $request, ResponseInterface $response, $strict = true);
}
