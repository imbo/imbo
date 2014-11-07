<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\Auth\UserLookupInterface,
    Imbo\Exception\RuntimeException,
    Imbo\Exception;

/**
 * Authentication event listener
 *
 * This listener enforces the usage of the signature and timestamp parameters when the user agent
 * wants to perform write operations (PUT/POST/DELETE).
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class Authenticate implements ListenerInterface {
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
    public static function getSubscribedEvents() {
        $callbacks = array();
        $events = array(
            'images.post',      // When adding images
            'image.delete',     // When deleting images
            'metadata.put',     // When adding/replacing metadata
            'metadata.post',    // When adding/patching metadata
            'metadata.delete',  // When deleting metadata
            'shorturls.post',   // Add a short URL
            'shorturls.delete', // Delete a collection of short URLs
            'shorturl.delete',  // Delete a single short URL
        );

        foreach ($events as $event) {
            $callbacks[$event] = array('authenticate' => 100);
        }

        return $callbacks;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(EventInterface $event) {
        $response = $event->getResponse();
        $request = $event->getRequest();

        // Whether or not the authentication info is in the request headers
        $fromHeaders = $request->headers->has('x-imbo-authenticate-timestamp') &&
                       $request->headers->has('x-imbo-authenticate-signature');

        // Fetch timestamp header, fallback to query param
        $timestamp = $request->headers->get(
            'x-imbo-authenticate-timestamp',
            $request->query->get('timestamp')
        );

        if (!$timestamp) {
            $exception = new RuntimeException('Missing authentication timestamp', 400);
            $exception->setImboErrorCode(Exception::AUTH_MISSING_PARAM);
        } else if (!$this->timestampIsValid($timestamp)) {
            $exception = new RuntimeException('Invalid timestamp: ' . $timestamp, 400);
            $exception->setImboErrorCode(Exception::AUTH_INVALID_TIMESTAMP);
        } else if ($this->timestampHasExpired($timestamp)) {
            $exception = new RuntimeException('Timestamp has expired: ' . $timestamp, 400);
            $exception->setImboErrorCode(Exception::AUTH_TIMESTAMP_EXPIRED);
        }

        if (isset($exception)) {
            throw $exception;
        }

        // Fetch signature header, fallback to query param
        $signature = $request->headers->get(
            'x-imbo-authenticate-signature',
            $request->query->get('signature')
        );

        if (!$signature) {
            $exception = new RuntimeException('Missing authentication signature', 400);
            $exception->setImboErrorCode(Exception::AUTH_MISSING_PARAM);
        }

        if (isset($exception)) {
            throw $exception;
        }

        $publicKey = $request->getPublicKey();
        $privateKeys = $event->getUserLookup()->getPrivateKeys(
            $publicKey,
            UserLookupInterface::MODE_READ_WRITE
        ) ?: [];

        $url = $request->getRawUri();

        if (!$fromHeaders) {
            // Remove the signature and timestamp from the query parameters as they are not used
            // when generating the HMAC
            $url = rtrim(preg_replace('/(?<=(\?|&))(signature|timestamp)=[^&]+&?/', '', $url), '&?');
        }

        // Add the URL used for auth to the response headers
        $response->headers->set('X-Imbo-AuthUrl', $url);

        if (!$this->signatureIsValid($request->getMethod(), $url, $publicKey, $privateKeys, $timestamp, $signature)) {
            $exception = new RuntimeException('Signature mismatch', 400);
            $exception->setImboErrorCode(Exception::AUTH_SIGNATURE_MISMATCH);

            throw $exception;
        }
    }

    /**
     * Check if the signature is valid
     *
     * @param string $httpMethod The current HTTP method
     * @param string $url The accessed URL
     * @param string $publicKey The current public key
     * @param array  $privateKeys The private keys to sign the hash with
     * @param string $timestamp A valid timestamp
     * @param string $signature The signature to compare with
     * @return boolean
     */
    private function signatureIsValid($httpMethod, $url, $publicKey, $privateKeys, $timestamp, $signature) {
        foreach ($privateKeys as $privateKey) {
            // Generate data for the HMAC
            $data = $httpMethod . '|' . $url . '|' . $publicKey . '|' . $timestamp;

            // Compare
            if ($signature === hash_hmac($this->algorithm, $data, $privateKey)) {
                return true;
            }
        }

        return false;
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
