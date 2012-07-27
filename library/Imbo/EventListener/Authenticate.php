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
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\Exception\RuntimeException,
    Imbo\Exception;

/**
 * Authentication event listener
 *
 * This listener enforces the usage of the signature and timestamp parameters when the user agent
 * wants to perform write operations (PUT/POST/DELETE).
 *
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Authenticate extends Listener implements ListenerInterface {
    /**
     * Max. diff to tolerate in the timestamp in seconds
     *
     * @var int
     */
    private $maxDiff = 120;

    /**
     * The algorithm to use when generating the HMAC
     *
     * @var string
     */
    private $algorithm = 'sha256';

    /**
     * {@inheritdoc}
     */
    public function getEvents() {
        return array(
            'status.get.pre',

            'image.put.pre',
            'image.post.pre',
            'image.delete.pre',

            'metadata.put.pre',
            'metadata.post.pre',
            'metadata.delete.pre',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function invoke(EventInterface $event) {
        $container = $event->getContainer();

        $config = $container->get('config');
        $auth = $config['auth'];

        $response = $container->get('response');
        $request  = $container->get('request');
        $query    = $request->getQuery();

        // Whether or not this is a status check
        $statusCheck = ($event->getName() === 'status.get.pre');

        // Required query parameters
        $requiredParams = array('signature', 'timestamp');

        if ($statusCheck) {
            // We have a status check. The public key is provided as a query parameter
            $requiredParams[] = 'publicKey';
        }

        // Check for signature and timestamp
        foreach ($requiredParams as $param) {
            if (!$query->has($param)) {
                if ($statusCheck) {
                    // This is a status check, let's bail.
                    return;
                }

                $e = new RuntimeException('Missing required authentication parameter: ' . $param, 400);
                $e->setImboErrorCode(Exception::AUTH_MISSING_PARAM);

                throw $e;
            }
        }

        // Fetch values we want to validate and remove them from the request
        $timestamp  = $query->get('timestamp');
        $signature  = $query->get('signature');
        $publicKey  = $statusCheck ? $query->get('publicKey') : $request->getPublicKey();

        if ($statusCheck && !isset($auth[$publicKey])) {
            $e = new RuntimeException('Unknown public key', 404);
            $e->setImboErrorCode(Exception::AUTH_UNKNOWN_PUBLIC_KEY);

            throw $e;
        }

        $privateKey = $statusCheck ? $auth[$publicKey] : $request->getPrivateKey();

        $query->remove('signature')
              ->remove('timestamp');

        if (!$this->timestampIsValid($timestamp)) {
            $e = new RuntimeException('Invalid timestamp: ' . $timestamp, 400);
            $e->setImboErrorCode(Exception::AUTH_INVALID_TIMESTAMP);

            throw $e;
        }

        if ($this->timestampHasExpired($timestamp)) {
            $e = new RuntimeException('Timestamp has expired: ' . $timestamp, 400);
            $e->setImboErrorCode(Exception::AUTH_TIMESTAMP_EXPIRED);

            throw $e;
        }

        $url = $request->getUrl();

        // Add the URL used for auth to the response headers
        $response->getHeaders()->set('X-Imbo-AuthUrl', $url);

        if (!$this->signatureIsValid($request->getMethod(), $url, $publicKey, $privateKey, $timestamp, $signature)) {
            $e = new RuntimeException('Signature mismatch', 400);
            $e->setImboErrorCode(Exception::AUTH_SIGNATURE_MISMATCH);

            throw $e;
        }
    }

    /**
     * Check if the signature is valid
     *
     * @param string $httpMethod The current HTTP method
     * @param string $url The accessed URL
     * @param string $publicKey The current public key
     * @param string $privateKey The private key to sign the hash with
     * @param string $timestamp A valid timestamp
     * @param string $signature The signature to compare with
     * @return boolean
     */
    private function signatureIsValid($httpMethod, $url, $publicKey, $privateKey, $timestamp, $signature) {
        // Generate data for the HMAC
        $data = $httpMethod . '|' . $url . '|' . $publicKey . '|' . $timestamp;

        // Compare
        return ($signature === hash_hmac($this->algorithm, $data, $privateKey));
    }

    /**
     * Check if the format of the timestamp is valid
     *
     * @param string $timestamp A string with a timestamp
     * @return boolean
     */
    private function timestampIsValid($timestamp) {
        if (!preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}(?:\.\d+)?Z$/', $timestamp)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the timestamp has expired
     *
     * @param string $timestamp A valid timestamp
     * @return boolean
     */
    private function timestampHasExpired($timestamp) {
        $year   = substr($timestamp, 0, 4);
        $month  = substr($timestamp, 5, 2);
        $day    = substr($timestamp, 8, 2);
        $hour   = substr($timestamp, 11, 2);
        $minute = substr($timestamp, 14, 2);
        $second = substr($timestamp, 17, 2);

        $timestamp = gmmktime($hour, $minute, $second, $month, $day, $year);

        $diff = time() - $timestamp;

        if ($diff > $this->maxDiff || $diff < -$this->maxDiff) {
            return true;
        }

        return false;
    }
}
