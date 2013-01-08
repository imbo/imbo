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
 * JSON formatter
 *
 * @package Http\Response
 * @subpackage Formatters
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class JSON implements FormatterInterface {
    /**
     * {@inheritdoc}
     */
    public function format(array $data, RequestInterface $request, ResponseInterface $response) {
        // Simply encode the data to JSON, no matter what resource we are dealing with
        $jsonEncoded = json_encode($data);
        $query = $request->getQuery();

        foreach (array('callback', 'jsonp', 'json') as $param) {
            if ($query->has($param)) {
                $jsonEncoded = sprintf("%s(%s)", $query->get($param), $jsonEncoded);
                break;
            }
        }

        return $jsonEncoded;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType() {
        return 'application/json';
    }
}
