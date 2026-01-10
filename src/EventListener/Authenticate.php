<?php declare(strict_types=1);

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Exception;
use Imbo\Exception\RuntimeException;
use Imbo\Http\Response\Response;
use Imbo\Resource;

use function in_array;

/**
 * Authentication event listener.
 *
 * This listener enforces the usage of the signature and timestamp parameters when the user agent
 * wants to perform write operations (PUT/POST/DELETE).
 */
class Authenticate implements ListenerInterface
{
    /**
     * Max. diff to tolerate in the timestamp in seconds.
     */
    private int $maxDiff = 120;

    /**
     * The algorithm to use when generating the HMAC.
     */
    private string $algorithm = 'sha256';

    public static function getSubscribedEvents(): array
    {
        $callbacks = [];
        $events = [
            Resource::GROUPS_POST,        // Create resource group
            Resource::GROUP_PUT,          // Update resource group
            Resource::GROUP_DELETE,       // Delete a resource group
            Resource::KEYS_POST,          // Create a public key
            Resource::KEY_PUT,            // Update a public key
            Resource::KEY_DELETE,         // Delete a public key
            Resource::ACCESS_RULE_DELETE, // Delete an access rule
            Resource::ACCESS_RULES_POST,  // Update access rules
            Resource::IMAGE_DELETE,       // When deleting images
            Resource::IMAGES_POST,        // When adding images
            Resource::METADATA_PUT,       // When adding/replacing metadata
            Resource::METADATA_POST,      // When adding/patching metadata
            Resource::METADATA_DELETE,    // When deleting metadata
            Resource::SHORTURL_DELETE,    // Delete a single short URL
            Resource::SHORTURLS_POST,     // Add a short URL
            Resource::SHORTURLS_DELETE,   // Delete a collection of short URLs

            'auth.authenticate', // Authenticate event
        ];

        foreach ($events as $event) {
            $callbacks[$event] = ['authenticate' => 100];
        }

        return $callbacks;
    }

    public function authenticate(EventInterface $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();
        $config = $event->getConfig();

        // Whether or not the authentication info is in the request headers
        $fromHeaders = $request->headers->has('x-imbo-authenticate-timestamp')
                       && $request->headers->has('x-imbo-authenticate-signature');

        // Fetch timestamp header, fallback to query param
        $timestamp = $request->headers->get(
            'x-imbo-authenticate-timestamp',
            $request->query->get('timestamp'),
        );

        if (!$timestamp) {
            $exception = new RuntimeException('Missing authentication timestamp', Response::HTTP_BAD_REQUEST);
            $exception->setImboErrorCode(Exception::AUTH_MISSING_PARAM);
        } elseif (!$this->timestampIsValid($timestamp)) {
            $exception = new RuntimeException('Invalid timestamp: '.$timestamp, Response::HTTP_BAD_REQUEST);
            $exception->setImboErrorCode(Exception::AUTH_INVALID_TIMESTAMP);
        } elseif ($this->timestampHasExpired($timestamp)) {
            $exception = new RuntimeException('Timestamp has expired: '.$timestamp, Response::HTTP_BAD_REQUEST);
            $exception->setImboErrorCode(Exception::AUTH_TIMESTAMP_EXPIRED);
        }

        if (isset($exception)) {
            throw $exception;
        }

        // Fetch signature header, fallback to query param
        $signature = $request->headers->get(
            'x-imbo-authenticate-signature',
            $request->query->get('signature'),
        );

        if (!$signature) {
            $exception = new RuntimeException('Missing authentication signature', Response::HTTP_BAD_REQUEST);
            $exception->setImboErrorCode(Exception::AUTH_MISSING_PARAM);
        }

        if (isset($exception)) {
            throw $exception;
        }

        $publicKey = $request->getPublicKey();

        if (null === $publicKey) {
            throw new RuntimeException('Missing public key', Response::HTTP_BAD_REQUEST);
        }

        $privateKey = $event->getAccessControl()->getPrivateKey($publicKey);

        $url = $request->getRawUri();

        if (!$fromHeaders) {
            // Remove the signature and timestamp from the query parameters as they are not used
            // when generating the HMAC
            $url = rtrim(preg_replace('/(?<=(\?|&))(signature|timestamp)=[^&]+&?/', '', $url), '&?');
        }

        // See if we should modify the protocol for the incoming request
        $uris = [$url];
        $protocol = $config['authentication']['protocol'];
        if ('both' === $protocol) {
            $uris = [
                preg_replace('#^https?#', 'http', $url),
                preg_replace('#^https?#', 'https', $url),
            ];
        } elseif (in_array($protocol, ['http', 'https'])) {
            $uris = [preg_replace('#^https?#', $protocol, $url)];
        }

        // Add the URL used for auth to the response headers
        $response->headers->set('X-Imbo-AuthUrl', implode(', ', $uris));

        foreach ($uris as $uri) {
            if ($this->signatureIsValid($request->getMethod(), $uri, $publicKey, $privateKey, $timestamp, $signature)) {
                return;
            }
        }

        $exception = new RuntimeException('Signature mismatch', Response::HTTP_BAD_REQUEST);
        $exception->setImboErrorCode(Exception::AUTH_SIGNATURE_MISMATCH);

        throw $exception;
    }

    /**
     * Check if the signature is valid.
     *
     * @param string $httpMethod The current HTTP method
     * @param string $url        The accessed URL
     * @param string $publicKey  The current public key
     * @param string $privateKey The private key to sign the hash with
     * @param string $timestamp  A valid timestamp
     * @param string $signature  The signature to compare with
     *
     * @return bool
     */
    private function signatureIsValid($httpMethod, $url, $publicKey, $privateKey, $timestamp, $signature)
    {
        // Generate data for the HMAC
        $data = $httpMethod.'|'.$url.'|'.$publicKey.'|'.$timestamp;

        // Compare
        if ($signature === hash_hmac($this->algorithm, $data, $privateKey)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the format of the timestamp is valid.
     *
     * @param string $timestamp A string with a timestamp
     *
     * @return bool
     */
    private function timestampIsValid($timestamp)
    {
        if (!preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}(?:\.\d+)?Z$/', $timestamp)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the timestamp has expired.
     *
     * @param string $timestamp A valid timestamp
     *
     * @return bool
     */
    private function timestampHasExpired($timestamp)
    {
        $year = (int) substr($timestamp, 0, 4);
        $month = (int) substr($timestamp, 5, 2);
        $day = (int) substr($timestamp, 8, 2);
        $hour = (int) substr($timestamp, 11, 2);
        $minute = (int) substr($timestamp, 14, 2);
        $second = (int) substr($timestamp, 17, 2);

        $timestamp = gmmktime($hour, $minute, $second, $month, $day, $year);

        $diff = time() - $timestamp;

        if ($diff > $this->maxDiff || $diff < -$this->maxDiff) {
            return true;
        }

        return false;
    }
}
