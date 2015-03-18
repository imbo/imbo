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
    Behat\Gherkin\Node\PyStringNode,
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
    public static function prepare(FeatureEvent $event) {
        // Drop mongo test collection which stores information regarding images, and the images
        // themselves
        $mongo = new MongoClient();
        $mongo->imbo_testing->drop();

        $cachePath = '/tmp/imbo-behat-image-transformation-cache';

        if (is_dir($cachePath)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($cachePath),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                $name = $file->getPathname();

                if (substr($name, -1) === '.') {
                    continue;
                }

                if ($file->isDir()) {
                    // Remove dir
                    rmdir($name);
                } else {
                    // Remove file
                    unlink($name);
                }
            }

            // Remove the directory itself
            rmdir($cachePath);
        }
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
     * @Given /^I do not specify a public and private key$/
     */
    public function removeClientAuth() {
        $this->publicKey = null;
        $this->privateKey = null;
    }

    /**
     * @Given /^I authenticate using "(.*?)"$/
     */
    public function authenticateRequest($method) {
        if ($method == 'access-token') {
            return new Given('I include an access token in the query');
        }

        if ($method == 'signature') {
            return new Given('I sign the request');
        }

        throw new \Exception('Unknown authentication method: ' . $method);
    }

    /**
     * @Given /^I include an access token in the query$/
     */
    public function appendAccessToken() {
        $this->client->getEventDispatcher()->addListener('request.before_send', function($event) {
            $request = $event['request'];
            $query = $request->getQuery();

            if (!$query->get('publicKey')) {
                $query->set('publicKey', $this->publicKey);
            }

            $query->remove('accessToken');
            $accessToken = hash_hmac('sha256', urldecode($request->getUrl()), $this->privateKey);
            $query->set('accessToken', $accessToken);
        }, -100);
    }

    /**
     * @Given /^I sign the request( using HTTP headers)?$/
     */
    public function signRequest($useHeaders = false) {
        $useHeaders = (boolean) $useHeaders;

        $this->client->getEventDispatcher()->addListener('request.before_send', function($event) use ($useHeaders) {
            $request = $event['request'];

            // Remove headers and query params that should not be present at this time
            $request->removeHeader('X-Imbo-Authenticate-Signature');
            $request->removeHeader('X-Imbo-Authenticate-Timestamp');
            $query = $request->getQuery();
            $query->remove('accessToken');
            $query->remove('signature');
            $query->remove('timestamp');

            // Add public key to query if we're told not to use headers
            if (!$useHeaders) {
                $query->set('publicKey', $this->publicKey);
            }

            $method = $request->getHeader('X-Http-Method-Override') ?: $request->getMethod();

            $timestamp = gmdate('Y-m-d\TH:i:s\Z');
            $data = $method . '|' . urldecode($request->getUrl()) . '|' . $this->publicKey . '|' . $timestamp;

            // Generate signature
            $signature = hash_hmac('sha256', $data, $this->privateKey);

            if ($useHeaders) {
                $request->addHeaders(array(
                    'X-Imbo-PublicKey'              => $this->publicKey,
                    'X-Imbo-Authenticate-Signature' => $signature,
                    'X-Imbo-Authenticate-Timestamp' => $timestamp,
                ));
            } else {
                $query->set('signature', $signature);
                $query->set('timestamp', $timestamp);
            }
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
        }

        assertSame($message, $errorMessage, 'Expected "' . $message. '", got "' . $errorMessage . '"');

        if ($code !== null) {
            $expected = (int) $code;
            $actual = (int) $errorCode;

            assertSame($expected, $actual, 'Expected "' . $expected . '", got "' . $actual . '"');
        }
    }

    /**
     * @Given /^"([^"]*)" exists in Imbo$/
     */
    public function addImageToImbo($imagePath) {
        return array(
            new Given('I use "publickey" and "privatekey" for public and private keys'),
            new Given('I sign the request'),
            new Given('I attach "' . $imagePath . '" to the request body'),
            new Given('I request "/users/publickey/images" using HTTP "POST"'),
        );
    }

    /**
     * @Given /^I specify the following transformations:$/
     */
    public function applyTransformations(PyStringNode $transformations) {
        foreach ($transformations->getLines() as $t) {
            $this->client->getEventDispatcher()->addListener('request.before_send', function($event) use ($t) {
                $event['request']->getQuery()->add('t', $t);
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setRequestHeader($header, $value) {
        if ($value === 'current-timestamp') {
            $value = gmdate('Y-m-d\TH:i:s\Z');
        }

        parent::setRequestHeader($header, $value);
    }

    /**
     * @When /^I request the added image as a "(jpg|png|gif)"$/
     */
    public function requestTheAddedImage($extension) {
        $identifier = $this->getLastResponse()->json()['imageIdentifier'];
        $this->request('/users/' . $this->publicKey . '/images/' . $identifier . '.' . $extension);
    }

    /**
     * @Given /^the image is deleted$/
     */
    public function deleteImage() {
        $identifier = $this->getLastResponse()->getHeaders()->toArray()['X-Imbo-ImageIdentifier'][0];

        $this->setClientAuth('publickey', 'privatekey');
        $this->signRequest();
        $this->request('/users/publickey/images/' . $identifier, 'DELETE');
    }

    /**
     * @Given /^the client IP is "([^"]*)"$/
     */
    public function setClientIp($ip) {
        $this->addHeaderToNextRequest('X-Client-Ip', $ip);
    }

    /**
     * @Given /^the image should not have any "([^"]*)" properties$/
     */
    public function assertImageProperties($tag) {
        $imagick = new \Imagick();
        $imagick->readImageBlob((string) $this->getLastResponse()->getBody());

        foreach ($imagick->getImageProperties() as $key => $value) {
            assertStringStartsNotWith($tag, $key, 'Properties exist that should have been stripped');
        }
    }

    /**
     * @Given /^the pixel at coordinate "([^"]*)" should have a color of "#([^"]*)"$/
     */
    public function assertImagePixelColor($coordinates, $expectedColor) {
        $coordinates = array_map('trim', explode(',', $coordinates));
        $coordinates = array_map('intval', $coordinates);

        $expectedColor = strtolower($expectedColor);

        $imagick = new \Imagick();
        $imagick->readImageBlob((string) $this->getLastResponse()->getBody());

        $pixel = $imagick->getImagePixelColor($coordinates[0], $coordinates[1]);
        $color = $pixel->getColor();

        $toHex = function($col) {
            return str_pad(dechex($col), 2, '0', STR_PAD_LEFT);
        };

        $hexColor = $toHex($color['r']) . $toHex($color['g']) . $toHex($color['b']);

        assertSame(
            $expectedColor,
            $hexColor,
            'Incorrect color at coordinate ' . implode(', ', $coordinates) .
            ', expected ' . $expectedColor . ', got ' . $hexColor
        );
    }

    /**
     * @Given /^Imbo uses the "([^"]*)" configuration$/
     */
    public function setImboConfigHeader($config) {
        $this->addHeaderToNextRequest('X-Imbo-Test-Config', $config);
    }

    /**
     * @Given /^the checksum of the image is "([^"]*)"$/
     */
    public function assertImageChecksum($checksum) {
        assertSame($checksum, md5((string) $this->getLastResponse()->getBody()), 'Checksum of the image in the last response did not match the expected checksum');
    }

    /**
     * @Given /^I generate a short URL with the following parameters:$/
     */
    public function generateShortImageUrl(PyStringNode $params) {
        $imageIdentifier = $this->getLastResponse()->json()['imageIdentifier'];

        return array(
            new Given('I use "publickey" and "privatekey" for public and private keys'),
            new Given('I sign the request'),
            new Given('the request body contains:', $params),
            new Given('I request "/users/publickey/images/' . $imageIdentifier . '/shorturls" using HTTP "POST"'),
        );
    }

    /**
     * @When /^I request the image using the generated short URL$/
     */
    public function requestImageUsingShortUrl() {
        $shortUrlId = $this->getLastResponse()->json()['id'];

        return array(
            new Given('the "Accept" request header is "image/*"'),
            new Given('I request "/s/' . $shortUrlId . '"'),
        );
    }

    /**
     * @Given /^I prime the database with "([^"]*)"$/
     */
    public function iPrimeTheAccessControlProviderWith($fixture)
    {
        $fixturePath = implode(DIRECTORY_SEPARATOR, [
            dirname(__DIR__),
            'fixtures',
            $fixture
        ]);

        if (!$fixturePath = realpath($fixturePath)) {
            throw new RuntimeException('Path "' . $fixturePath . '" is invalid');
        }

        $mongo = (new MongoClient())->imbo_testing;

        $fixtures = require $fixturePath;
        foreach ($fixtures as $collection => $data) {
            $mongo->$collection->drop();

            if (!!$data) {
                $mongo->$collection->batchInsert($data);
            }
        }
    }
}
