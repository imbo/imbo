<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

use Behat\Behat\Event\SuiteEvent,
    Behat\Behat\Exception\PendingException;

// Use the RESTContext
require 'RESTContext.php';

/**
 * Imbo Context
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Functional tests
 */
class ImboContext extends RESTContext {
    /**
     * The public key used by the client
     *
     * @var string
     */
    private $publicKey;

    /**
     * The private key used by the client
     *
     * @var string
     */
    private $privateKey;

    /**
     * @Given /^there are no Imbo issues$/
     */
    public function thereAreNoImboIssues() {}

    /**
     * @Given /^I use "([^"]*)" and "([^"]*)" for public and private keys$/
     */
    public function setClientAuth($publicKey, $privateKey) {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    /**
     * @Given /^I include an access token in the query$/
     */
    public function appendAccessToken() {
        $this->client->getEventDispatcher()->addListener('request.before_send', function($event) {
            $request = $event['request'];
            $accessToken = hash_hmac('sha256', $request->getUrl(), $this->privateKey);
            $request->getQuery()->set('accessToken', $accessToken);
        });
    }

    /**
     * @Given /^I sign the request$/
     */
    public function signRequest() {
        $this->client->getEventDispatcher()->addListener('request.before_send', function($event) {
            $request = $event['request'];

            $timestamp = gmdate('Y-m-d\TH:i:s\Z');
            $data = $request->getMethod() . '|' . $request->getUrl() . '|' . $this->publicKey . '|' . $timestamp;

            // Generate signature
            $signature = hash_hmac('sha256', $data, $this->privateKey);

            $query = $request->getQuery();
            $query->set('signature', rawurlencode($signature));
            $query->set('timestamp', rawurlencode($timestamp));
        });
    }

    /**
     * @Given /^the Imbo error message is "([^"]*)"(?: and the error code is "([^"]*)")?$/
     */
    public function assertImboError($message, $code = null) {
        $response = $this->getLastResponse();
        $contentType = $response->getContentType();

        if ($contentType === 'application/json') {
            $data = $response->json();
            $errorMessage = $data['error']['message'];
            $errorCode = $data['error']['imboErrorCode'];
        } else if ($contentType === 'application/xml') {
            $data = $response->xml();
            $errorMessage = (string) $data->error->message;
            $errorCode = $data->error->imboErrorCode;
        } else {
            throw new PendingException('Not added support for html yet');
        }

        assertSame($message, $errorMessage, 'Expected "' . $message. '", got "' . $errorMessage . '"');

        if ($code !== null) {
            $expected = (int) $code;
            $actual = (int) $errorCode;

            assertSame($expected, $actual, 'Expected "' . $expected . '", got "' . $actual . '"');
        }
    }
}
