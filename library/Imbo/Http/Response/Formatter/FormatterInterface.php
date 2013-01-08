<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http\Response\Formatter;

use Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\ResponseInterface;

/**
 * Interface for formatters
 *
 * @package Interfaces
 * @subpackage Http\Response\Formatters
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
interface FormatterInterface {
    /**
     * Format data
     *
     * @param array $data The data to format
     * @param RequestInterface $request The current request
     * @param ResponseInterface $response The current response
     * @return string Formatted data ready to be sent to the client
     */
    function format(array $data, RequestInterface $request, ResponseInterface $response);

    /**
     * Get the content type for the current formatter
     *
     * Return the content type for the current formatter, excluding the character set, for instance
     * 'application/json'.
     *
     * @return string
     */
    function getContentType();
}
