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

namespace PHPIMS\Client\Driver;

use PHPIMS\Client\DriverInterface;
use PHPIMS\Client\Response;

/**
 * cURL client driver
 *
 * This class is a driver for the client using the cURL functions.
 *
 * @package PHPIMS
 * @subpackage Client
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class Curl implements DriverInterface {
    /**
     * The cURL handle used by the client
     *
     * @var resource
     */
    private $curlHandle = null;

    /**
     * Parameters for the driver
     *
     * @var array
     */
    private $params = array(
        'timeout'        => 2,
        'connectTimeout' => 2,
    );

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     */
    public function __construct(array $params = array()) {
        $this->curlHandle = curl_init();

        if (!empty($params)) {
            $this->params = array_merge($this->params, $params);
        }

        curl_setopt_array($this->curlHandle, array(
            CURLOPT_USERAGENT      => __CLASS__,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_HTTPHEADER     => array('Expect:'),
        ));
    }

    /**
     * Class destructor
     */
    public function __destruct() {
        curl_close($this->curlHandle);
    }

    /**
     * @see PHPIMS\Client\DriverInterface::post()
     */
    public function post($url, array $metadata = null, $filePath = null) {
        $postFields = array(
            'metadata' => json_encode($metadata),
        );

        if ($filePath !== null) {
            $postFields['file'] = '@' . $filePath;
        }

        curl_setopt_array($this->curlHandle, array(
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_CUSTOMREQUEST => 'POST',
        ));

        return $this->request($url);
    }

    /**
     * @see PHPIMS\Client\DriverInterface::get()
     */
    public function get($url) {
        curl_setopt_array($this->curlHandle, array(
            CURLOPT_HTTPGET => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        return $this->request($url);
    }

    /**
     * @see PHPIMS\Client\DriverInterface::head()
     */
    public function head($url) {
        curl_setopt_array($this->curlHandle, array(
            CURLOPT_NOBODY => true,
            CURLOPT_CUSTOMREQUEST => 'HEAD',
        ));

        return $this->request($url);
    }

    /**
     * @see PHPIMS\Client\DriverInterface::delete()
     */
    public function delete($url) {
        curl_setopt_array($this->curlHandle, array(
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
        ));

        return $this->request($url);
    }

    /**
     * Make a request
     *
     * This method will make a request to $url with the current options set in the cURL handle
     * resource.
     *
     * @param string $url The URL to request
     * @return PHPIMS\Client\Response
     * @throws PHPIMS\Client\Driver\Exception
     */
    protected function request($url) {
        var_dump("requestURL: " . $url);
        // Set the timeout options
        curl_setopt_array($this->curlHandle, array(
            CURLOPT_URL            => $url,
            CURLOPT_CONNECTTIMEOUT => $this->params['connectTimeout'],
            CURLOPT_TIMEOUT        => $this->params['timeout'],
        ));

        $content = curl_exec($this->curlHandle);
        $connectTime  = (int) curl_getinfo($this->curlHandle, CURLINFO_CONNECT_TIME);
        $transferTime = (int) curl_getinfo($this->curlHandle, CURLINFO_TOTAL_TIME);
        $responseCode = (int) curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);

        if ($content === false) {
            if ($connectTime >= $this->params['connectTimeout']) {
                throw new Exception('An error occured. Request timed out while connecting (limit: ' . $this->params['connectTimeout'] . 's).');
            } else if ($transferTime >= $this->params['timeout']) {
                throw new Exception('An error occured. Request timed out during transfer (limit: ' . $this->params['timeout'] . 's).');
            }
            
            throw new Exception('An error occured. Could not complete request (Response code: ' . $responseCode . ').');
        }

        $response = Response::factory($content, $responseCode);

        return $response;
    }

    /**
     * @see PHPIMS\Client\DriverInterface::addImage()
     */
    public function addImage($path, $url, array $metadata = null) {
        return $this->post($url, $metadata, $path);
    }
}
