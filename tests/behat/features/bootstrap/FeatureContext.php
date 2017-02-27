<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

use Imbo\BehatApiExtension\Context\ApiContext;
use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Behat\Gherkin\Node\TableNode;
use Assert\Assertion;

/**
 * Imbo Context
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Functional tests
 */
class FeatureContext extends ApiContext {
    /**
     * Names for middlewares
     *
     * @var string
     */
    const MIDDLEWARE_SIGN_REQUEST = 'sign-request';
    const MIDDLEWARE_APPEND_ACCESS_TOKEN = 'append-access-token';

    /**
     * The user used by the client
     *
     * @var string
     */
    private $user;

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
     * The name of the current configuration file
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
     * Array container for the history middleware
     *
     * @param array
     */
    private $history = [];

    /**
     * Keys for the users
     *
     * @var array
     */
    private $keys = [
        'user' => [
            'publicKey' => 'publicKey',
            'privateKey' => 'privateKey',
        ],
    ];

    /**
     * Manipulate the handler stack of the client for all tests
     *
     * - Add the history middleware to record all request / responses in the $this->history array
     *
     * @param ClientInterface $client A GuzzleHttp\Client instance
     * @return self
     */
    public function setClient(ClientInterface $client) {
        $client->getConfig()['handler']->push(Middleware::history($this->history));

        return parent::setClient($client);
    }

    /**
     * Drop mongo test collection which stores information regarding images, and the images
     * themselves
     *
     * @param BeforeFeatureScope $scope
     *
     * @BeforeScenario
     */
    public static function prepare(BeforeScenarioScope $scope) {
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
     * Set a request header that will have Imbo load a custom configuration file
     *
     * @param string $configFile Custom configuration file to use for the next request (file must
     *                           reside in the tests/behat/imbo-configs directory)
     * @throws InvalidArgumentException
     *
     * @Given Imbo uses the :configFile configuration
     */
    public function setImboConfigHeader($configFile) {
        $dir = __DIR__ . '/../../imbo-configs';

        if (!is_file($dir . '/' . $configFile)) {
            throw new InvalidArgumentException(sprintf(
                'Configuration file "%s" does not exist in the "%s" directory.',
                $configFile,
                $dir
            ));
        }

        $this->currentConfig = $configFile;
        $this->setRequestHeader('X-Imbo-Test-Config-File', $configFile);
    }

    /**
     * Allow IPs to view stats by specifying a net mask
     *
     * This feature is implemented in the stats-access-and-custom-stats.php custom configuration
     * file.
     *
     * @param string $mask Specify which subnet mask who are allowed to view stats
     * @return self
     *
     * @Given the stats are allowed by :mask
     */
    public function statsAllowedBy($mask) {
        return $this->setRequestHeader('X-Imbo-Stats-Allowed-By', $mask);
    }

    /**
     * Send a request header with the next request, informing Imbo which adapter to take down
     *
     * This feature is implemented in the status.php custom configuration file.
     *
     * @param string $adapter Which adapter to take down
     * @return self
     *
     * @Given /^the (storage|database) is down$/
     */
    public function forceAdapterFailure($adapter) {
        if ($adapter === 'storage') {
            $header = 'X-Imbo-Status-Storage-Failure';
        } else {
            $header = 'X-Imbo-Status-Database-Failure';
        }

        return $this->setRequestHeader($header, 1);
    }

    /**
     * Sign the request with the given public key and private key using HTTP headers
     *
     * @param string $publicKey The public key to sign the URL with
     * @param string $privateKey The private key to sign the URL with
     * @return self
     *
     * @Given I sign the request with :publicKey and :privateKey using HTTP headers
     */
    public function signRequestUsingHttpHeaders($publicKey, $privateKey) {
        return $this->signRequest($publicKey, $privateKey, true);
    }

    /**
     * Signal that the request needs to be signed before sent
     *
     * This step adds a "sign-request" middleware to the request. The middleware should be executed
     * last.
     *
     * @param string $publicKey The public key to sign the URL with
     * @param string $privateKey The private key to sign the URL with
     * @param boolean $useHeaders Whether or not to put the signature in the request HTTP headers
     * @return self
     *
     * @Given I sign the request with :publicKey and :privateKey
     */
    public function signRequest($publicKey, $privateKey, $useHeaders = false) {
        $useHeaders = (boolean) $useHeaders;

        // Fetch the handler stack and push a signature function to it
        $stack = $this->client->getConfig('handler');
        $stack->push(Middleware::mapRequest(function(RequestInterface $request) use ($publicKey, $privateKey, $useHeaders, $stack) {
            // Add public key as a query parameter if we're told not to use headers. We do this
            // before the signing below since this parameter needs to be a part of the data that
            // will be used for signing
            if (!$useHeaders) {
                $request = $request->withUri(Uri::withQueryValue(
                    $request->getUri(),
                    'publicKey',
                    $publicKey
                ));
            }

            // Fetch the HTTP method
            $httpMethod = $request->getHeaderLine('X-Http-Method-Override') ?: $request->getMethod();

            // Prepare the data that will be signed using the private key
            $timestamp = gmdate('Y-m-d\TH:i:s\Z');
            $data = sprintf('%s|%s|%s|%s',
                $httpMethod,
                urldecode((string) $request->getUri()),
                $publicKey,
                $timestamp
            );

            // Generate signature
            $signature = hash_hmac('sha256', $data, $privateKey);

            if ($useHeaders) {
                $request = $request
                    ->withHeader('X-Imbo-PublicKey', $publicKey)
                    ->withHeader('X-Imbo-Authenticate-Signature', $signature)
                    ->withHeader('X-Imbo-Authenticate-Timestamp', $timestamp);
            } else {
                $request = $request->withUri(
                    Uri::withQueryValue(
                        Uri::withQueryValue(
                            $request->getUri(),
                            'signature',
                            $signature
                        ),
                        'timestamp',
                        $timestamp
                    )
                );
            }

            // Remove this middleware as we don't want the signing to happen more than once
            $stack->remove(self::MIDDLEWARE_SIGN_REQUEST);

            return $request;
        }), self::MIDDLEWARE_SIGN_REQUEST);

        return $this;
    }

    /**
     * Append an access token as a query parameter, using the specified keys
     *
     * @param string $publicKey The public key to use
     * @param string $privateKey The private key to use
     *
     * @Given I include an access token in the query using :publicKey and :privateKey
     */
    public function appendAccessToken($publicKey, $privateKey) {
        // Fetch the handler stack and push an access token function to it
        $stack = $this->client->getConfig('handler');
        $stack->push(Middleware::mapRequest(function(RequestInterface $request) use ($publicKey, $privateKey, $stack) {
            $uri = $request->getUri();

            // Set the public key and remove a possible accessToken query parameter
            $uri = Uri::withQueryValue($uri, 'publicKey', $publicKey);
            $uri = Uri::withoutQueryValue($uri, 'accessToken');

            // Generate the access token and append to the query
            $accessToken = hash_hmac('sha256', urldecode((string) $uri), $privateKey);
            $uri = Uri::withQueryValue($uri, 'accessToken', $accessToken);

            // Remove the middleware from the stack
            $stack->remove(self::MIDDLEWARE_APPEND_ACCESS_TOKEN);

            // Return Uri with query string including the access token
            return $request->withUri($uri);
        }), self::MIDDLEWARE_APPEND_ACCESS_TOKEN);
    }

    /**
     * Add an image to Imbo for a given user
     *
     * This is a convenience step mostly used for backgrounds in tests. It combines a few other
     * steps:
     *
     * - add an image to the request
     * - sign the request
     * - issue a POST
     *
     * The users, public keys and private keys are specified in the test configuration, and the
     * map of keys exist in $this->keys.
     *
     * Since this method might be executed in between other steps we will not have a fresh instance
     * of the client after this step is finished, so we need to clean up after we're done by
     * resetting the request and request options.
     *
     * @param string $imagePath Path to the image, relative to the project root path
     * @param string $user The user who will own the image
     * @throws InvalidArgumentException Throws an exception if the user specified does not have a
     *                                  set of keys.
     * @return self
     *
     * @Given :imagePath exists for user :user
     */
    public function addUserImageToImbo($imagePath, $user) {
        // See if the user specified has a set of keys
        if (!isset($this->keys[$user])) {
            throw new InvalidArgumentException(sprintf('No keys exist for user "%s".', $user));
        }

        // Store the original request
        $originalRequest = clone $this->request;
        $originalRequestOptions = $this->requestOptions;

        $this
            // Attach the file to the request body
            ->setRequestBody(fopen($imagePath, 'r'))

            // Sign the request
            ->signRequest($this->keys[$user]['publicKey'], $this->keys[$user]['privateKey'])

            // Request the endpoint for adding the image
            ->requestPath(sprintf('/users/%s/images', $user), 'POST');

        // Store the mapping of path => image identifier and the image data
        $imageIdentifier = json_decode((string) $this->response->getBody())->imageIdentifier;
        $this->imageIdentifiers[$imagePath] = $imageIdentifier;

        // Reset the request / response
        $this->request = $originalRequest;
        $this->requestOptions = $originalRequestOptions;
        $this->response = null;

        return $this;
    }

    /**
     * Set a request header that is picked up by the "stats-access-and-custom-stats.php" custom
     * configuration file to test the access part of the event listener
     *
     * @param string $ip The IP address to set
     * @return self
     *
     * @Given the client IP is :ip
     */
    public function setClientIp($ip) {
        $this->setRequestHeader('X-Client-Ip', $ip);

        return $this;
    }

    /**
     * Make a request to the previously added image (in the same scenario)
     *
     * This method will loop through the history in reverse order and look for responses which
     * contains image identifiers. The first one found will be requested.
     *
     * @param string $method The HTTP method to use
     * @param string $extension
     * @throws RuntimeException Throws an exception if there is no response in the history that has
     *                          added an image.
     * @return self
     *
     * @When I request the previously added image
     * @When I request the previously added image using HTTP :method
     */
    public function requestPreviouslyAddedImage($method = 'GET', $extension = null) {
        // Go back in the history until we have a request with an image
        foreach (array_reverse($this->history) as $transaction) {
            $response = $transaction['response'];

            if ($response) {
                $body = json_decode((string) $response->getBody());

                if (!empty($body->imageIdentifier)) {
                    // Fetch the user from the request URI in the same transaction
                    $request = $transaction['request'];
                    $matches = [];
                    preg_match('|/users/(.+?)/images|', (string) $request->getUri(), $matches);

                    $path = sprintf('/users/%s/images/%s', $matches[1], $body->imageIdentifier);

                    if ($extension) {
                        // Append extension to the path
                        $path .= '.' . $extension;
                    }

                    return $this->requestPath($path, $method);
                }
            }
        }

        // No hit
        throw new RuntimeException(
            'Could not find any responses in the history with an image identifier.'
        );
    }

    /**
     * Request the previously added image as a specific extension
     *
     * @param string $extension Extension of the image: jpg, gif or png
     * @throws InvalidArgumentException Throws an extension if the given extension is invalid
     * @return self
     *
     * @When I request the previously added image as a :extension
     * @When I request the previously added image as a :extension using HTTP :method
     */
    public function requestPreviouslyAddedImageAsType($extension, $method = 'GET') {
        if (!in_array($extension, ['gif', 'png', 'jpg'])) {
            throw new InvalidArgumentException(sprintf('Invalid extension: "%s".', $extension));
        }

        return $this->requestPreviouslyAddedImage($method, $extension);
    }

    /**
     * Assert the contents of an imbo error message
     *
     * @param string $message The error message
     * @param int $code The error code
     * @throws InvalidArgumentException
     * @return self
     *
     * @Then the Imbo error message is :message
     * @Then the Imbo error message is :message and the error code is :code
     */
    public function assertImboError($message, $code = null) {
        $this->requireResponse();

        if ($this->response->getStatusCode() < 400) {
            throw new InvalidArgumentException(
                'The status code of the last response is lower than 400, so it is not considered an error.'
            );
        }

        $body = json_decode((string) $this->response->getBody());
        $actualMessage = $body->error->message;
        $actualCode = $body->error->imboErrorCode;

        Assertion::same(
            $message,
            $actualMessage,
            sprintf('Expected error message "%s", got "%s".', $message, $actualMessage)
        );

        if ($code !== null) {
            $code = (int) $code;
            $actualCode = (int) $actualCode;

            Assertion::same(
                $code,
                $actualCode,
                sprintf('Expected imbo error code "%d", got "%d".', $code, $actualCode)
            );
        }

        return $this;
    }

    /**
     * Add a transformation to the query parameter for the next request
     *
     * @param string $transformation The value of the transformation, for instance "border"
     * @return self
     *
     * @Given I specify :transformation as transformation
     */
    public function applyTransformation($transformation) {
        if (!isset($this->requestOptions['query']['t'])) {
            $this->requestOptions['query']['t'] = [];
        }

        $this->requestOptions['query']['t'][] = $transformation;
    }

    /**
     * Assert the width of the image in the current response
     *
     * @param int $width
     * @return self
     *
     * @Then the image width is :width
     */
    public function assertImageWidth($width) {
        $this->requireResponse();

        $width = (int) $width;

        list($actualWidth) = getimagesizefromstring((string) $this->response->getBody());

        Assertion::same(
            $width,
            $actualWidth,
            sprintf('Incorrect image width, expected %d, got %d.', $width, $actualWidth)
        );

        return $this;
    }

    /**
     * Assert the height of image in the current response
     *
     * @param int $height
     * @return self
     *
     * @Then the image height is :height
     */
    public function assertImageHeight($height) {
        $this->requireResponse();

        $height = (int) $height;

        list($actualWidth, $actualHeight) = getimagesizefromstring((string) $this->response->getBody());
        unset($actualWidth);

        Assertion::same(
            $height,
            $actualHeight,
            sprintf('Incorrect image height, expected %d, got %d.', $height, $actualHeight)
        );

        return $this;
    }

    /**
     * Assert the dimensions of the image in the current response
     *
     * @param string $dimension Image dimension as "<width>x<height>", for instance "1024x768"
     * @throws InvalidArgumentException
     * @return self
     *
     * @Then the image dimension is :dimension
     */
    public function assertImageDimension($dimension) {
        $this->requireResponse();

        $match = [];
        preg_match('/^(?<width>[\d]+)x(?<height>[\d]+)$/', $dimension, $match);

        if (!$match) {
            throw new InvalidArgumentException(sprintf(
                'Invalid dimension value: "%s". Specify "<width>x<height>".',
                $dimension
            ));
        }

        $width = (int) $match['width'];
        $height = (int) $match['height'];

        list($actualWidth, $actualHeight) = getimagesizefromstring((string) $this->response->getBody());

        Assertion::same(
            $width,
            $actualWidth,
            sprintf('Incorrect image width, expected %d, got %d.', $width, $actualWidth)
        );

        Assertion::same(
            $height,
            $actualHeight,
            sprintf('Incorrect image height, expected %d, got %d.', $height, $actualHeight)
        );
    }

    /**
     * Assert the hex value of a given coordinate in the image found in the current response
     *
     * @param string $coordinates X and Y coordinates, separated by a comma
     * @param string $color Hex color value
     * @return self
     *
     * @Then the pixel at coordinate :coordinates has a color of :color
     */
    public function assertImagePixelColor($coordinates, $color) {
        $this->requireResponse();

        $info = $this->getImagePixelInfo($coordinates);
        $color = ltrim(strtolower($color), '#');

        Assertion::same(
            $color,
            $info['color'],
            sprintf(
                'Incorrect color at coordinate "%s", expected "%s", got "%s".',
                $coordinates,
                $color,
                $info['color']
            )
        );

        return $this;
    }

    /**
     * Assert the alpha value of a given coordinate in the image found in the current response
     *
     * @param string $coordinates X and Y coordinates, separated by a comma
     * @param float $alpha Alpha value
     * @return self
     *
     * @Given the pixel at coordinate :coordinates has an alpha of :alpha
     */
    public function assertImagePixelAlpha($coordinates, $alpha) {
        $this->requireResponse();

        $info = $this->getImagePixelInfo($coordinates);
        $alpha = (float) $alpha;

        Assertion::same(
            $alpha,
            $info['alpha'],
            sprintf(
                'Incorrect alpha value at coordinate "%s", expected "%f", got "%f".',
                $coordinates,
                $alpha,
                $info['alpha']
            )
        );

        return $this;
    }

    /**
     * Get the pixel info for given coordinates from the image in the current response
     *
     * @param string $coordinates
     * @throws InvalidArgumentException Throws an exception if the coordinates value is invalid
     * @return array
     */
    private function getImagePixelInfo($coordinates) {
        $this->requireResponse();

        $match = [];
        preg_match('/^(?<x>[\d]+),(?<y>[\d]+)$/', $coordinates, $match);

        if (!$match) {
            throw new InvalidArgumentException(sprintf('Invalid coordinates: "%s".', $coordinates));
        }

        $x = (int) $match['x'];
        $y = (int) $match['y'];

        $imagick = new Imagick();
        $imagick->readImageBlob((string) $this->response->getBody());

        $pixel = $imagick->getImagePixelColor($x, $y);
        $color = $pixel->getColor();

        $toHex = function($col) {
            return str_pad(dechex($col), 2, '0', STR_PAD_LEFT);
        };

        $hexColor = $toHex($color['r']) . $toHex($color['g']) . $toHex($color['b']);

        return [
            'color' => $hexColor,
            'alpha' => (float) $pixel->getColorValue(Imagick::COLOR_ALPHA),
        ];
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
     * {@inheritdoc}
     */
    public function setRequestHeader($header, $value) {
        if ($value === 'current-timestamp') {
            $value = gmdate('Y-m-d\TH:i:s\Z');
        }

        return parent::setRequestHeader($header, $value);
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
            $this->addHeaderToNextRequest('X-Imbo-Test-Config-File', $this->currentConfig);
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
            $this->addHeaderToNextRequest('X-Imbo-Test-Config-File', $this->currentConfig);
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
     * Check the size of the response body (not the Content-Length response header)
     *
     * @param int $expetedSize The size we are expecting
     * @Then the response body size is :expectedSize
     */
    public function assertResponseBodySize($expectedSize) {
        $this->requireResponse();

        Assertion::same(
            $actualSize = strlen((string) $this->response->getBody()),
            (int) $expectedSize,
            sprintf('Expected response body size: %d, actual: %d', $expectedSize, $actualSize)
        );
    }

    /**
     * Set a query string parameter
     *
     * @param string $name The name of the parameter
     * @param mixed $value The value for the parameter
     * @Given the query string parameter :name is set to :value
     */
    public function setRequestQueryParameter($name, $value) {
        if (!is_array($this->requestOptions['query'])) {
            $this->requestOptions['query'] = [];
        }

        $this->requestOptions['query'][$name] = $value;
    }

    /**
     * Set multiple query string parameters
     *
     * @param TableNode $table Query parameters
     * @Given the following query string parameters are set:
     */
    public function setRequestQueryParameters(TableNode $table) {
        foreach ($table as $row) {
            $this->addRequestQueryParameter($row['name'], $row['value']);
        }
    }

    /**
     * Set an array-type query parameter
     *
     * @param string $name The name of the parameter
     * @param TableNode $table Values for the query parameter
     * @Given the query string parameter :name has the following values:
     */
    public function setRequestQueryParameterValues($name, TableNode $table) {
        $values = [];

        foreach ($table as $row) {
            $values[] = $row['value'];
        }

        $this->addRequestQueryParameter($name, $values);
    }
}
