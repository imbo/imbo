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
class PHPIMS_Client_Driver_Curl extends PHPIMS_Client_Driver_Abstract {
    /**
     * The cURL handle used by the client
     *
     * @var resource
     */
    protected $curlHandle = null;

    /**
     * Class destructor
     */
    public function __destruct() {
        curl_close($this->curlHandle);
    }

    /**
     * @see PHPIMS_Client_Driver_Abstract::init()
     */
    protected function init() {
        $this->curlHandle = curl_init();

        curl_setopt_array($this->curlHandle, array(
            CURLOPT_USERAGENT      => __CLASS__,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_HTTPHEADER     => array('Expect:'),
        ));
    }

    /**
     * @see PHPIMS_Client_Driver_Interface::post()
     */
    public function post($url, array $data = null) {
        curl_setopt_array($this->curlHandle, array(
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $data,
        ));

        return $this->request($url);
    }

    /**
     * @see PHPIMS_Client_Driver_Interface::get()
     */
    public function get($url) {
        curl_setopt_array($this->curlHandle, array(
            CURLOPT_HTTPGET => true,
        ));

        return $this->request($url);
    }

    /**
     * @see PHPIMS_Client_Driver_Interface::head()
     */
    public function head($url) {
        throw new PHPIMS_Client_Driver_Exception('not yet implemented');
    }

    /**
     * @see PHPIMS_Client_Driver_Interface::delete()
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
     * @return PHPIMS_Client_Response
     * @throws PHPIMS_Client_Driver_Exception
     */
    protected function request($url) {
        // Set the timeout options
        curl_setopt_array($this->curlHandle, array(
            CURLOPT_URL            => $url,
            CURLOPT_CONNECTTIMEOUT => $this->getClient()->getConnectTimeout(),
            CURLOPT_TIMEOUT        => $this->getClient()->getTimeout(),
        ));

        $content = curl_exec($this->curlHandle);
        $responseCode = (int) curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);

        if ($content === false) {
            throw new PHPIMS_Client_Driver_Exception('An error occured. Could not complete request.');
        }

        $response = PHPIMS_Client_Response::factory($content, $responseCode);

        return $response;
    }

    /**
     * @see PHPIMS_Client_Driver_Interface::addImage()
     */
    public function addImage($path, $url, array $metadata = null) {
        $data = array();

        if ($metadata !== null) {
            $data = $metadata;
        }

        // Add the file reference
        $data['file'] = '@' . $path;

        return $this->post($url, $data);
    }
}