<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

use Behat\Behat\Event\FeatureEvent,
    Behat\Behat\Event\SuiteEvent,
    Behat\Behat\Exception\PendingException,
    Behat\Behat\Context\Step\Given;

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
     * @BeforeFeature
     */
    public static function cleanDatabase(FeatureEvent $event) {
        // Drop mongo test collection
        $mongo = new MongoClient();
        $mongo->imbo_testing->drop();
    }

    /**
     * @Given /^the (storage|database) is down$/
     */
    public function forceAdapterFailure($adapter) {
        $this->client->getEventDispatcher()->addListener('request.before_send', function($event) use ($adapter) {
            $event['request']->getQuery()->set($adapter . 'Down', 1);
        });
    }

    /**
     * @Given /^the database and the storage is down$/
     */
    public function forceBothAdapterFailure() {
        return array(
            new Given('the storage is down'),
            new Given('the database is down'),
        );
    }

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
        }, -100);
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
            $query->set('signature', $signature);
            $query->set('timestamp', $timestamp);
        }, -100);
    }

    /**
     * @Given /^I specify "([^"]*)" as transformation$/
     */
    public function applyTransformation($transformation) {
        $this->client->getEventDispatcher()->addListener('request.before_send', function($event) use ($transformation) {
            $event['request']->getQuery()->set('t', array($transformation));
        });
    }

    /**
     * @Given /^the (width|height) of the image is "([^"]*)"$/
     */
    public function theWidthOfTheImageIs($value, $size) {
        $image = (string) $this->getLastResponse()->getBody();
        $size = (int) $size;

        $info = getimagesizefromstring($image);

        if ($value === 'width') {
            $index = 0;
        } else {
            $index = 1;
        }

        assertSame($size, $info[$index], 'Incorrect ' . $value . ', expected ' . $size . ', got ' . $info[$index]);
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

    /**
     * @Given /^"([^"]*)" exists in Imbo with identifier "([^"]*)"$/
     */
    public function addImageToImbo($imagePath, $imageIdentifier) {
        return array(
            new Given('I use "publickey" and "privatekey" for public and private keys'),
            new Given('I sign the request'),
            new Given('I attach "tests/Imbo/Fixtures/image1.png" to the request body'),
            new Given('I request "/users/publickey/images/' . $imageIdentifier . '" using HTTP "PUT"'),
        );
    }
}
