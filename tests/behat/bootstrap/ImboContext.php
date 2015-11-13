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
     * The user used by the client
     *
     * @var string
     */
    private $user = 'user';

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
     * Holds the configuration file specified in the current feature
     *
     * @var string
     */
    private $currentConfig;

    /**
     * An array of urls for added images, keyed by local file path
     *
     * @var array
     */
    private $imageUrls = [];

    /**
     * An array of image identifiers for added images, keyed by local file path
     *
     * @var array
     */
    private $imageIdentifiers = [];

    /**
     * Holds the current image target URL - used when the same image is requested
     * over several tests in the same feature
     *
     * @var string
     */
    private static $testImageUrl;

    /**
     * Holds the image identifier of the current testing target
     *
     * @var string
     */
    private static $testImageIdentifier;

    /**
     * Holds the path to the image currently used as the testing target
     *
     * @var string
     */
    private static $testImagePath;

    /**
     * Holds the current feature for this test image
     *
     * @var string
     */
    private static $testImageFeature;

    /**
     * @BeforeFeature
     */
    public static function prepare(FeatureEvent $event) {
        // Drop mongo test collection which stores information regarding images, and the images
        // themselves
        $mongo = new MongoClient();
        $mongo->imbo_testing->drop();

        $cachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imbo-behat-image-transformation-cache';

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
        return [
            new Given('the storage is down'),
            new Given('the database is down'),
        ];
    }

    /**
     * @Given /^I use "([^"]*)" and "([^"]*)" for public and private keys$/
     */
    public function setClientAuth($publicKey, $privateKey) {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;

        $this->client->getEventDispatcher()->addListener('request.before_send', function($event) {
            $request = $event['request'];
            $request->addHeaders([
                'X-Imbo-PublicKey' => $this->publicKey
            ]);
        }, -100);
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
            $request->removeHeader('X-Imbo-PublicKey');
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
                $request->addHeaders([
                    'X-Imbo-PublicKey'              => $this->publicKey,
                    'X-Imbo-Authenticate-Signature' => $signature,
                    'X-Imbo-Authenticate-Timestamp' => $timestamp,
                ]);
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
            $event['request']->getQuery()->set('t', [$transformation]);
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

        try {
            if ($contentType === 'application/json') {
                $data = $response->json();
                $errorMessage = $data['error']['message'];
                $errorCode = $data['error']['imboErrorCode'];
            } else if ($contentType === 'application/xml') {
                $data = $response->xml();
                $errorMessage = (string) $data->error->message;
                $errorCode = $data->error->imboErrorCode;
            }
        } catch (\Exception $e) {
            throw new RuntimeException(
                "Unable to parse response: \n" .
                $response->getMessage() . "\n\n" .
                $e->getMessage()
            );
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
        $this->addUserImageToImbo($imagePath, 'user');
    }

    /**
     * @Given /^"([^"]*)" exists for user "([^"]*)" in Imbo$/
     */
    public function addUserImageToImbo($imagePath, $user) {
        $this->setClientAuth('publickey', 'privatekey');
        $this->signRequest();
        $this->attachFileToRequestBody($imagePath);
        $this->request('/users/' . $user . '/images/', 'POST');
        $this->imageUrls[$imagePath] = $this->getPreviouslyAddedImageUrl();
        $this->imageIdentifiers[$imagePath] = $this->getPreviouslyAddedImageIdentifier();
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
     * @Given /^"([^"]*)" is used as the test image( for the "([^"]*)" feature)?$/
     */
    public function imageIsUsedAsTestImage($testImagePath, $forFeature = null, $feature = null) {
        if (self::$testImagePath === $testImagePath && $feature &&
            self::$testImageFeature === $feature) {
            return;
        }

        $this->addImageToImbo($testImagePath);
        self::$testImageIdentifier = $this->getPreviouslyAddedImageIdentifier();
        self::$testImageUrl = $this->getPreviouslyAddedImageUrl();
        self::$testImagePath = $testImagePath;
        self::$testImageFeature = $feature;
    }

    /**
     * @When /^I request the test image(?: as a "([^"]*)")?$/
     */
    public function requestTestImage($format = null) {
        $url = self::$testImageUrl . ($format ? '.' . $format : '');
        return [
            new Given('I request "' . $url . '" using HTTP "GET"'),
        ];
    }

    /**
     * @When /^I request the test image using HTTP "([^"]*)"$/
     */
    public function requestTestImageUsingHttpMethod($method) {
        $url = self::$testImageUrl;
        return [
            new Given('I request "' . $url . '" using HTTP "' . $method . '"'),
        ];
    }

    /**
     * @When /^I request the metadata of the test image(?: using HTTP "(.*?)")?$/
     */
    public function requestMetadataOfTestImage($method = 'GET') {
        $url = self::$testImageUrl . '/meta';
        return [
            new Given('I request "' . $url . '" using HTTP "' . $method . '"'),
        ];
    }

    /**
     * @When /^I request the metadata of the test image as "(xml|json)"$/
     */
    public function requestMetadataOfTestImageInFormat($format = null) {
        $url = self::$testImageUrl . '/meta' . ($format ? '.' . $format : '');
        return [
            new Given('I request "' . $url . '" using HTTP "GET"'),
        ];
    }

    /**
     * @When /^I request the (?:previously )?added image(?: with the query string "([^"]*)")?$/
     */
    public function requestPreviouslyAddedImage($queryParams = '') {
        $url = $this->getPreviouslyAddedImageUrl() . $queryParams;
        return [
            new Given('I request "' . $url . '" using HTTP "GET"'),
        ];
    }

    /**
     * @When /^I request the (?:previously )?added image using HTTP "([^"]*)"$/
     */
    public function requestPreviouslyAddedImageWithHttpMethod($method) {
        $url = $this->getPreviouslyAddedImageUrl();
        return [
            new Given('I request "' . $url . '" using HTTP "' . $method . '"'),
        ];
    }


    /**
     * @When /^I request the (?:previously )?added image as a "(jpg|png|gif)"$/
     */
    public function requestTheAddedImage($extension) {
        $url = $this->getPreviouslyAddedImageUrl() . '.' . $extension;
        return [
            new Given('I request "' . $url . '" using HTTP "GET"'),
        ];
    }

    /**
     * @When /^I request the metadata of the previously added image as "(xml|json)"$/
     */
    public function requestMetadataOfPreviouslyAddedImageInFormat($format = null) {
        $url = $this->getPreviouslyAddedImageUrl() . '/meta' . ($format ? '.' . $format : '');
        return [
            new Given('I request "' . $url . '" using HTTP "GET"'),
        ];
    }

    /**
     * @When /^I request the metadata of the previously added image(?: using HTTP "(.*?)")?$/
     */
    public function requestMetadataOfPreviouslyAddedImage($method = 'GET') {
        $url = $this->getPreviouslyAddedImageUrl() . '/meta';
        return [
            new Given('I request "' . $url . '" using HTTP "' . $method . '"'),
        ];
    }

    /**
     * @When /^I request the image resource for "([^"]*)"(?: as a "(png|gif|jpg)")?(?: using HTTP "([^"]*)")?$/
     */
    public function requestImageResourceForLocalImage($imagePath, $format = null, $method = 'GET') {
        if (!isset($this->imageUrls[$imagePath])) {
            throw new RuntimeException('Image URL for "' . $imagePath . '" not found');
        }

        $url = $this->imageUrls[$imagePath];
        if ($format) {
            $url .= '.' . $format;
        }

        return [
            new Given('I request "' . $url . '" using HTTP "' . $method . '"'),
        ];
    }

    /**
     * @Given /^I append a query string parameter, "([^"]*)" with the image identifier of "([^"]*)"$/
     */
    public function appendQueryStringParamWithImageIdentifierForLocalImage($queryParam, $imagePath) {
        if (!isset($this->imageIdentifiers[$imagePath])) {
            throw new RuntimeException('Image identifier for "' . $imagePath . '" not found');
        }

        $this->appendQueryStringParameter($queryParam, $this->imageIdentifiers[$imagePath]);
    }

    /**
     * @Given /^the image is deleted$/
     */
    public function deleteImage() {
        $identifier = $this->getLastResponse()->getHeaders()->toArray()['X-Imbo-ImageIdentifier'][0];

        $this->setClientAuth('publickey', 'privatekey');
        $this->signRequest();
        $this->request('/users/user/images/' . $identifier, 'DELETE');
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
        $info = $this->getImagePixelInfo($coordinates);
        $expectedColor = strtolower($expectedColor);

        assertSame(
            $expectedColor,
            $info['color'],
            'Incorrect color at coordinate ' . $coordinates .
            ', expected ' . $expectedColor . ', got ' . $info['color']
        );
    }

    /**
     * @Given /^the pixel at coordinate "([^"]*)" should have an alpha of "([^"]*)"$/
     */
    public function assertImagePixelAlpha($coordinates, $expectedAlpha) {
        $info = $this->getImagePixelInfo($coordinates);
        $expectedAlpha = (float) $expectedAlpha;

        assertSame(
            $expectedAlpha,
            $info['alpha'],
            'Incorrect alpha value at coordinate ' . $coordinates .
            ', expected ' . $expectedAlpha . ', got ' . $info['alpha']
        );
    }

    /**
     * @Given /^Imbo uses the "([^"]*)" configuration$/
     */
    public function setImboConfigHeader($config) {
        $this->currentConfig = $config;
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
        $lastResponse = $this->getLastResponse();

        preg_match('/\/users\/([^\/]+)/', $lastResponse->getInfo('url'), $matches);
        $user = $matches[1];

        $imageIdentifier = $lastResponse->json()['imageIdentifier'];
        $params = array_merge(json_decode((string) $params, true), [
            'imageIdentifier' => $imageIdentifier,
        ]);

        return [
            new Given('the request body contains:', new PyStringNode(json_encode($params))),
            new Given('I request "/users/' . $user . '/images/' . $imageIdentifier . '/shorturls" using HTTP "POST"'),
        ];
    }

    /**
     * @When /^I request the image using the generated short URL$/
     */
    public function requestImageUsingShortUrl() {
        $shortUrlId = $this->getLastResponse()->json()['id'];

        return [
            new Given('the "Accept" request header is "image/*"'),
            new Given('I request "/s/' . $shortUrlId . '"'),
        ];
    }

    /**
     * @Given /^I prime the database with "([^"]*)"$/
     */
    public function iPrimeTheDatabaseWith($fixture) {
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

            if ($data) {
                $mongo->$collection->batchInsert($data);
            }
        }
    }

    /**
     * @Given /^the ACL rule under public key "([^"]*)" with ID "([^"]*)" should not exist( anymore)?$/
     */
    public function aclRuleWithIdShouldNotExist($publicKey, $aclId) {
        if ($this->currentConfig) {
            $this->addHeaderToNextRequest('X-Imbo-Test-Config', $this->currentConfig);
        }

        $url = '/keys/' . $publicKey . '/access/' . $aclId;
        return [
            new Given('I use "acl-checker" and "foobar" for public and private keys'),
            new Given('I include an access token in the query'),
            new Given('I request "' . $url . '" using HTTP "GET"'),
            new Given('I should get a response with "404 Access rule not found"')
        ];
    }

    /**
     * @Given /^the "([^"]*)" public key should not exist( anymore)?$/
     */
    public function publicKeyShouldNotExist($publicKey) {
        if ($this->currentConfig) {
            $this->addHeaderToNextRequest('X-Imbo-Test-Config', $this->currentConfig);
        }

        $url = '/keys/' . $publicKey;
        return [
            new Given('I use "acl-creator" and "someprivkey" for public and private keys'),
            new Given('I include an access token in the query'),
            new Given('I request "' . $url . '" using HTTP "HEAD"'),
            new Given('I should get a response with "404 Public key not found"')
        ];
    }

    /**
     * @Given /^Imbo starts with an empty database$/
     */
    public function imboStartsWithEmptyDatabase() {
        $mongo = new MongoClient();
        $mongo->imbo_testing->drop();
    }

    /**
     * @Given /^I use "([^"]*)" as the watermark image with "([^"]*)" as parameters$/
     */
    public function specifyAsTheWatermarkImage($watermarkPath, $parameters = '') {
        $this->addImageToImbo($watermarkPath);
        $imageIdentifier = $this->getPreviouslyAddedImageIdentifier();
        $params = empty($parameters) ? '' : ',' . $parameters;
        $transformation = 'watermark:img=' . $imageIdentifier . $params;

        return [
            new Given('I specify "' . $transformation . '" as transformation')
        ];
    }

    /**
     * Get the previously added image identifier
     *
     * @throws RuntimeException If previous response did not include image identifier
     * @return string
     */
    private function getPreviouslyAddedImageIdentifier() {
        $response = $this->getLastResponse()->json();
        if (!isset($response['imageIdentifier'])) {
            throw new RuntimeException(
                'Image identifier was not present in previous response, response: ' .
                $this->getLastResponse()->getBody(true)
            );
        }

        return $response['imageIdentifier'];
    }

    /**
     * Get the previously added image URL
     *
     * @throws RuntimeException If previous response did not include image identifier
     * @return string
     */
    private function getPreviouslyAddedImageUrl() {
        $identifier = $this->getPreviouslyAddedImageIdentifier();
        return '/users/' . $this->user . '/images/' . $identifier;
    }

    /**
     * Get the pixel info for given coordinates, from the image returned in the previous response
     *
     * @param  string $coordinates
     * @return array
     */
    private function getImagePixelInfo($coordinates) {
        $coordinates = array_map('trim', explode(',', $coordinates));
        $coordinates = array_map('intval', $coordinates);

        $imagick = new \Imagick();
        $imagick->readImageBlob((string) $this->getLastResponse()->getBody());

        $pixel = $imagick->getImagePixelColor($coordinates[0], $coordinates[1]);
        $color = $pixel->getColor();

        $toHex = function($col) {
            return str_pad(dechex($col), 2, '0', STR_PAD_LEFT);
        };

        $hexColor = $toHex($color['r']) . $toHex($color['g']) . $toHex($color['b']);

        return [
            'color' => $hexColor,
            'alpha' => (float) $pixel->getColorValue(\Imagick::COLOR_ALPHA),
        ];
    }
}
