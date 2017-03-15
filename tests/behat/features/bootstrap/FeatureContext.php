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
use Imbo\BehatApiExtension\ArrayContainsComparator;
use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Behat\Gherkin\Node\TableNode;
use Assert\Assertion;
use Micheh\Cache\CacheUtil;

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
    const MIDDLEWARE_HISTORY = 'history';

    /**
     * @var CacheUtil
     */
    private $cacheUtil;

    /**
     * Class constructor
     *
     * @param CacheUtil $cacheUtil
     */
    public function __construct(CacheUtil $cacheUtil = null) {
        if ($cacheUtil === null) {
            $cacheUtil = new CacheUtil();
        }

        $this->cacheUtil = $cacheUtil;
    }

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
        'other-user' => [
            'publicKey' => 'publicKey',
            'privateKey' => 'privateKey',
        ],
    ];

    /**
     * Whether or not the access token handler is currently active
     *
     * @var boolean
     */
    private $accessTokenHandlerIsActive = false;

    /**
     * Whether or not the authentication handler is currently active
     *
     * @var boolean
     */
    private $authenticationHandlerIsActive = false;

    /**
     * Manipulate the handler stack of the client for all tests
     *
     * - Add the history middleware to record all request / responses in the $this->history array
     *
     * @param ClientInterface $client A GuzzleHttp\Client instance
     * @return self
     */
    public function setClient(ClientInterface $client) {
        $handlerStack = $client->getConfig()['handler'];

        // Remove a potential handler with the same name
        $handlerStack->remove(self::MIDDLEWARE_HISTORY);
        $handlerStack->push(Middleware::history($this->history), self::MIDDLEWARE_HISTORY);

        return parent::setClient($client);
    }

    /**
     * Add custom functions to the comparator
     *
     * The following functions are added and can be used with the
     * `Then the response body contains JSON:` step:
     *
     * - @isDate(): Check if a field that is supposed to represent a date is property formatted
     *
     * @param ArrayContainsComparator $comparator
     * @return self
     */
    public function setArrayContainsComparator(ArrayContainsComparator $comparator) {
        $comparator->addFunction('isDate', [$this, 'isDate']);

        return parent::setArrayContainsComparator($comparator);
    }

    /**
     * Function for the array contains comparator to validate a date string
     *
     * Validates the following date format:
     *
     * 'D, d M Y H:i:s' . ' GMT'
     *
     * @param string $date The string to validate
     * @throws InvalidArgumentException
     * @return void
     */
    public function isDate($date) {
        if (!preg_match('/^[A-Z][a-z]{2}, [\d]{2} [A-Z][a-z]{2} [\d]{4} [\d]{2}:[\d]{2}:[\d]{2} GMT$/', $date)) {
            throw new InvalidArgumentException(sprintf(
                'Date is not properly formatted: "%s".',
                $date
            ));
        }
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
     * Sign the request using HTTP headers
     *
     * @return self
     *
     * @Given I sign the request using HTTP headers
     */
    public function signRequestUsingHttpHeaders() {
        return $this->signRequest(true);
    }

    /**
     * Sign the request
     *
     * This step adds a "sign-request" middleware to the request. The middleware should be executed
     * last.
     *
     * @param boolean $useHeaders Whether or not to put the signature in the request HTTP headers
     * @return self
     *
     * @Given I sign the request
     */
    public function signRequest($useHeaders = false) {
        if ($this->authenticationHandlerIsActive) {
            throw new RuntimeException(
                'The authentication handler is currently added to the stack. It can not be added more than once.'
            );
        }

        // Set the token handler as active
        $this->authenticationHandlerIsActive = true;

        $useHeaders = (boolean) $useHeaders;

        // Fetch the handler stack and push a signature function to it
        $stack = $this->client->getConfig('handler');
        $stack->push(Middleware::mapRequest(function(RequestInterface $request) use ($useHeaders, $stack) {
            // Add public key as a query parameter if we're told not to use headers. We do this
            // before the signing below since this parameter needs to be a part of the data that
            // will be used for signing
            if (!$useHeaders) {
                $request = $request->withUri(Uri::withQueryValue(
                    $request->getUri(),
                    'publicKey',
                    $this->publicKey
                ));
            }

            // Fetch the HTTP method
            $httpMethod = $request->getHeaderLine('X-Http-Method-Override') ?: $request->getMethod();

            // Prepare the data that will be signed using the private key
            $timestamp = gmdate('Y-m-d\TH:i:s\Z');
            $data = sprintf('%s|%s|%s|%s',
                $httpMethod,
                urldecode((string) $request->getUri()),
                $this->publicKey,
                $timestamp
            );

            // Generate signature
            $signature = hash_hmac('sha256', $data, $this->privateKey);

            if ($useHeaders) {
                $request = $request
                    ->withHeader('X-Imbo-PublicKey', $this->publicKey)
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
            $this->authenticationHandlerIsActive = false;
            $stack->remove(self::MIDDLEWARE_SIGN_REQUEST);

            return $request;
        }), self::MIDDLEWARE_SIGN_REQUEST);

        return $this;
    }

    /**
     * Append an access token as a query parameter
     *
     * @param boolean $allRequests Whether or not to keep the handler for all requests or not
     * @throws RuntimeException Method can not be called if the handler is still active
     * @return self
     *
     * @Given /^I include an access token in the query string( for all requests)?$/
     */
    public function appendAccessToken($allRequests = false) {
        if ($this->accessTokenHandlerIsActive) {
            throw new RuntimeException(
                'The access token handler is currently added to the stack. It can not be added more than once.'
            );
        }

        // Set the token handler as active
        $this->accessTokenHandlerIsActive = true;

        // Fetch the handler stack and push an access token function to it
        $stack = $this->client->getConfig('handler');
        $stack->push(Middleware::mapRequest(function(RequestInterface $request) use ($stack, $allRequests) {
            $uri = $request->getUri();

            // Set the public key and remove a possible accessToken query parameter
            $uri = Uri::withQueryValue($uri, 'publicKey', $this->publicKey);
            $uri = Uri::withoutQueryValue($uri, 'accessToken');

            // Generate the access token and append to the query
            $accessToken = hash_hmac('sha256', urldecode((string) $uri), $this->privateKey);
            $uri = Uri::withQueryValue($uri, 'accessToken', $accessToken);

            // Remove the middleware from the stack unless we want to keep adding the token
            if (!$allRequests) {
                // Deactivate the handler
                $this->accessTokenHandlerIsActive = false;
                $stack->remove(self::MIDDLEWARE_APPEND_ACCESS_TOKEN);
            }

            // Return Uri with query string including the access token
            return $request->withUri($uri);
        }), self::MIDDLEWARE_APPEND_ACCESS_TOKEN);

        return $this;
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
     * @param PyStringNode $metadata Metadata to add to the image
     * @throws InvalidArgumentException Throws an exception if the user specified does not have a
     *                                  set of keys.
     * @return self
     *
     * @Given :imagePath exists for user :user
     * @Given :imagePath exists for user :user with the following metadata:
     */
    public function addUserImageToImbo($imagePath, $user, PyStringNode $metadata = null) {
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
            ->setPublicAndPrivateKey($this->keys[$user]['publicKey'], $this->keys[$user]['privateKey'])
            ->signRequest()

            // Request the endpoint for adding the image
            ->requestPath(sprintf('/users/%s/images', $user), 'POST');

        // Store the mapping of path => image identifier and the image data
        $responseBody = json_decode((string) $this->response->getBody(), true);

        if (empty($responseBody['imageIdentifier'])) {
            throw new RuntimeException(sprintf(
                'Image was not successfully added. Response body: %s',
                print_r($responseBody, true)
            ));
        }

        $imageIdentifier = $responseBody['imageIdentifier'];
        $this->imageIdentifiers[$imagePath] = $imageIdentifier;
        $this->imageUrls[$imagePath] = sprintf('/users/%s/images/%s', $user, $imageIdentifier);

        // Attach metadata
        if ($metadata !== null) {
            $this
                // Attach the file to the request body
                ->setRequestBody((string) $metadata)

                // Sign the request
                ->setPublicAndPrivateKey($this->keys[$user]['publicKey'], $this->keys[$user]['privateKey'])
                ->signRequest()

                // Request the endpoint for adding the image
                ->requestPath(sprintf('/users/%s/images/%s/metadata', $user, $imageIdentifier), 'POST');
        }

        // Reset the request / response
        $this->publicKey = null;
        $this->privateKey = null;
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
        return $this->setRequestQueryParameter('t[]', $transformation);
    }

    /**
     * Add one or more transformations to the query parameter for the next request
     *
     * @param PyStringNode $transformations
     * @return self
     *
     * @Given I specify the following transformations:
     */
    public function applyTransformations(PyStringNode $transformations) {
        foreach (explode("\n", (string) $transformations) as $transformation) {
            $this->applyTransformation(trim($transformation));
        }

        return $this;
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
     * @return array Returns an array with two keys:
     *               - `color`: Hex color of the pixel.
     *               - `alpha`: Alpha value of the pixel.
     */
    private function getImagePixelInfo($coordinates) {
        $this->requireResponse();

        $match = [];
        preg_match('/^(?<x>[\d]+),(?<y>[\d]+)$/', $coordinates, $match);

        if (!$match) {
            throw new InvalidArgumentException(sprintf(
                'Invalid coordinates: "%s". Format is "<w>x<h>", no spaces allowed.',
                $coordinates
            ));
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
     * Prive the database with content from a PHP script
     *
     * @param string $fixture Name of a PHP file in the tests/behat/fixtures directory that returns
     *                        an array.
     * @throws InvalidArgumentException Throws an exception if $fixture does not exist, or if it
     *                                  does not return an array.
     * @return self
     *
     * @Given I prime the database with :fixture
     */
    public function primeDatabase($fixture) {
        $fixtureDir = realpath(implode(DIRECTORY_SEPARATOR, [
            dirname(dirname(__DIR__)),
            'fixtures'
        ]));
        $fixturePath = $fixtureDir . DIRECTORY_SEPARATOR . $fixture;

        if (!is_file($fixturePath)) {
            throw new InvalidArgumentException(sprintf(
                'Fixture file "%s" does not exist in "%s".',
                $fixture,
                $fixtureDir
            ));
        }

        $mongo = (new MongoClient())->imbo_testing;

        $fixtures = require $fixturePath;

        if (!is_array($fixtures)) {
            throw new InvalidArgumentException(sprintf(
                'Fixture "%s" does not return an array.',
                $fixturePath
            ));
        }

        foreach ($fixtures as $collection => $data) {
            $mongo->$collection->drop();

            if ($data) {
                $mongo->$collection->batchInsert($data);
            }
        }
    }

    /**
     * Authenticate the request using some authentication method
     *
     * @param string $method The method of authentication
     * @throws InvalidArgumentException Throws an exception if an invalid method is used
     * @return self
     *
     * @Given I authenticate using :method
     */
    public function authenticateRequest($method) {
        if ($method === 'access-token') {
            return $this->appendAccessToken();
        } else if ($method === 'signature') {
            return $this->signRequest();
        } else if ($method === 'signature (headers)') {
            return $this->signRequestUsingHttpHeaders();
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid authentication method: "%s".',
            $method
        ));
    }

    /**
     * Make sure an ACL rule has been deleted
     *
     * @param string $publicKey The public key
     * @param string $aclId The ACL ID to check
     * @return self
     *
     * @Then the ACL rule under public key :publicKey with ID :aclId no longer exists
     */
    public function aclRuleWithIdShouldNotExist($publicKey, $aclId) {
        // Append an access token with the current public / private keys, and request the given
        // ACL rule
        $this
            ->appendAccessToken()
            ->requestPath(sprintf('/keys/%s/access/%s', $publicKey, $aclId));

        $expectedStatusLine = '404 Access rule not found';
        $actualStatusLine = sprintf(
            '%d %s',
            $this->response->getStatusCode(),
            $this->response->getReasonPhrase()
        );

        Assertion::same(
            $expectedStatusLine,
            $actualStatusLine,
            sprintf(
                'ACL rule still exists. Expected "%s", got "%s".',
                $expectedStatusLine,
                $actualStatusLine
            )
        );

        return $this;
    }

    /**
     * Make sure a public does not exist
     *
     * @param string $publicKey The public key to check for
     * @return self
     *
     * @Then the :publicKey public key no longer exists
     */
    public function assertPublicKeyDoesNotExist($publicKey) {
        // Append an access token with the current public / private keys, and request the given
        // public key
        $this
            ->appendAccessToken()
            ->requestPath(sprintf('/keys/%s', $publicKey), 'HEAD');

        $expectedStatusLine = '404 Public key not found';
        $actualStatusLine = sprintf(
            '%d %s',
            $this->response->getStatusCode(),
            $this->response->getReasonPhrase()
        );

        Assertion::same(
            $expectedStatusLine,
            $actualStatusLine,
            sprintf(
                'Public key still exists. Expected "%s", got "%s".',
                $expectedStatusLine,
                $actualStatusLine
            )
        );

        return $this;
    }

    /**
     * Set the public and private keys to be used for signing the request / generating the access
     * token
     *
     * @param string $publicKey The public key to set
     * @param string $privateKey The private key to set
     * @return self
     *
     * @Given I use :publicKey and :privateKey for public and private keys
     */
    public function setPublicAndPrivateKey($publicKey, $privateKey) {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;

        // Add the request header
        $this->setRequestHeader('X-Imbo-PublicKey', $publicKey);

        return $this;
    }

    /**
     * Set a query string parameter
     *
     * @param string $name The name of the parameter
     * @param mixed $value The value for the parameter
     * @return self
     *
     * @Given the query string parameter :name is set to :value
     */
    public function setRequestQueryParameter($name, $value) {
        if (empty($this->requestOptions['query'])) {
            $this->requestOptions['query'] = [];
        }

        // If the name ends with [] we remove that from the name, and convert the value to an array
        if (substr($name, -2) === '[]') {
            $name = substr($name, 0, -2);

            if (isset($this->requestOptions['query'][$name]) && !is_array($this->requestOptions['query'][$name])) {
                // The field already exists, but not as an array
                throw new InvalidArgumentException(sprintf(
                    'The "%s" query parameter already exists and it\'s not an array, so can\'t append more values to it.',
                    $name
                ));
            } else if (!isset($this->requestOptions['query'][$name])) {
                // The field does not exist, set it to an empty array
                $this->requestOptions['query'][$name] = [];
            }

            // Append the value
            $this->requestOptions['query'][$name][] = $value;
        } else {
            // Set the key => value
            $this->requestOptions['query'][$name] = $value;
        }

        return $this;
    }

    /**
     * Replay the last request
     *
     * This method can be used to replay a request, with or without a different HTTP method. If the
     * public and private keys have been set the method will append an access token.
     *
     * @param string $method Optional HTTP method. If not set the HTTP method from the previous
     *                       request will be used.
     * @throws RuntimeException Throws an exception if no request have been made yet.
     * @return self
     *
     * @When I replay the last request
     * @When I replay the last request using HTTP :method
     */
    public function makeSameRequest($method = null) {
        if (!$this->request) {
            throw new RuntimeException('No request have been made yet.');
        }

        $this->setRequestMethod($method ?: $this->request->getMethod());

        if ($this->publicKey && $this->privateKey) {
            $this->appendAccessToken();
        }

        return $this->sendRequest();
    }

    /**
     * Check whether or not the response can be cached
     *
     * @param boolean $cacheable
     * @return self
     *
     * @Then /^the response can (not )?be cached$/
     */
    public function assertCacheability($cacheable = true) {
        $this->requireResponse();

        if ($cacheable !== true) {
            $cacheable = false;
        }

        Assertion::same(
            $cacheable,
            $this->cacheUtil->isCacheable($this->response),
            $cacheable ?
                'Response was supposed to be cacheble, but it\'s not.' :
                'Response was not supposed to be cacheable, but it is.'
        );

        return $this;
    }

    /**
     * Validate the max-age directive of the cache-control response header
     *
     * @param int $expected Expected max-age
     * @throws RuntimeException
     * @return self
     *
     * @Then the response has a max-age of :max seconds
     */
    public function assertMaxAge($expected) {
        $this->requireResponse();

        $cacheControl = $this->response->getHeaderLine('cache-control');

        if (!$cacheControl) {
            throw new RuntimeException('Response does not have a cache-control header.');
        }

        $match = [];
        preg_match('/max-age=(?<maxAge>[\d]+)/i', $cacheControl, $match);

        if (!$match) {
            throw new RuntimeException(sprintf(
                'Response cache-control header does not include a max-age directive: "%s".',
                $cacheControl
            ));
        }

        $expected = (int) $expected;
        $maxAge = (int) $match['maxAge'];

        Assertion::same(
            $expected,
            $maxAge,
            sprintf(
                'The max-age directive in the cache-control header is not correct. Expected %d, got %d. Complete cache-control header: "%s".',
                $expected,
                $maxAge,
                $cacheControl
            )
        );

        return $this;
    }

    /**
     * Verify that the response has a specific cache-control directive
     *
     * @param string $directive
     * @throws RuntimeException Throws an exception if the response does not have a cache-control
     *                          directive, or if the validation fails
     * @return self
     *
     * @Then the response has a :directive cache-control directive
     */
    public function assertResponseHasCacheControlDirective($directive) {
        $this->requireResponse();

        $cacheControl = $this->response->getHeaderLine('cache-control');

        if (!$cacheControl) {
            throw new RuntimeException('Response does not have a cache-control header.');
        }

        Assertion::contains(
            $cacheControl,
            $directive,
            sprintf(
                'The cache-control header does not contain the "%s" directive. Complete cache-control header: "%s".',
                $directive,
                $cacheControl
            )
        );

        return $this;
    }

    /**
     * Verify that the response does not have a given cache-control directive
     *
     * @param string $directive
     * @throws RuntimeException Throws an exception if the response does not have a cache-control
     *                          directive, or if the validation fails
     * @return self
     *
     * @Then the response does not have a :directive cache-control directive
     */
    public function assertResponseDoesNotHaveCacheControlDirective($directive) {
        $this->requireResponse();

        $cacheControl = $this->response->getHeaderLine('cache-control');

        if (!$cacheControl) {
            throw new RuntimeException('Response does not have a cache-control header.');
        }

        if (strpos($cacheControl, $directive) !== false) {
            throw new RuntimeException(
                sprintf(
                    'The cache-control header contains the "%s" directive when it should not. Complete cache-control header: "%s".',
                    $directive,
                    $cacheControl
                )
            );
        }

        return $this;
    }

    /**
     * Request the metadata of the previously added image
     *
     * @param string $method The HTTP method to use when fetching the metadata
     * @throws RuntimeException Throws an exception if no image can be found in the history
     * @return self
     *
     * @When I request the metadata of the previously added image
     * @When I request the metadata of the previously added image using HTTP :method
     */
    public function requestMetadataOfPreviouslyAddedImage($method = 'GET') {
        // Go back in the history until we have a response with an image identifier
        foreach (array_reverse($this->history) as $transaction) {
            $responseBody = json_decode((string) $transaction['response']->getBody(), true);

            if (isset($responseBody['imageIdentifier'])) {
                $match = [];
                preg_match(
                    '|^/users/(?<user>.*?)/images|',
                    (string) $transaction['request']->getUri()->getPath(),
                    $match
                );

                if ($match) {
                    $user = $match['user'];
                    $path = sprintf(
                        '/users/%s/images/%s/metadata',
                        $user,
                        $responseBody['imageIdentifier']
                    );

                    return $this->requestPath($path, $method);
                }
            }
        }

        // No hit
        throw new RuntimeException(
            'Could not find any response in the history with an image identifier.'
        );
    }

    /**
     * Request an image using a local file path
     *
     * This method can be used to fetch images that has been added to Imbo earlier via the
     * `addUserImageToImbo` method, that is triggered by `Given :imagePath exists for user :user`.
     *
     * @param string $localPath The local path for the image that was added earlier
     * @param string $format Optional format of the image (png|gif|jpg)
     * @param string $method Optional HTTP method to use
     * @throws InvalidArgumentException
     * @return self
     *
     * @When /^I request the image resource for "([^"]*)"(?: as a "(png|gif|jpg)")?(?: using HTTP "([^"]*)")?$/
     */
    public function requestImageResourceForLocalImage($localPath, $format = null, $method = 'GET') {
        if (!isset($this->imageUrls[$localPath])) {
            throw new InvalidArgumentException(sprintf(
                'Image URL for image with path "%s" can not be found.',
                $localPath
            ));
        }

        $url = $this->imageUrls[$localPath];

        if ($format) {
            // Append extension if specified
            $url .= '.' . $format;
        }

        return $this->requestPath($url, $method);
    }

    /**
     * Compare a specific header in the last $num responses
     *
     * @param int $num The number of responses to compare. Must be at least 2.
     * @param string $header The response header to compare
     * @param boolean $unique Whether or not the values should be unique
     * @throws InvalidArgumentException|RuntimeException
     * @return self
     *
     * @Then /^the last ([\d]+) "([^"]+)" response headers are (not )?the same$/
     */
    public function assertLastResponseHeadersAreNotTheSame($num, $header, $unique = false) {
        $num = (int) $num;

        if ($num < 2) {
            throw new InvalidArgumentException(sprintf(
                'Need to compare at least 2 responses, got %d.',
                $num
            ));
        }

        $numResponses = count($this->history);

        if ($numResponses < $num) {
            throw new InvalidArgumentException(sprintf(
                'Not enough responses in the history. Need at least %d, there are currently %d.',
                $num,
                $numResponses
            ));
        }

        $values = [];

        foreach (array_slice(array_reverse($this->history), 0, $num) as $transaction) {
            $response = $transaction['response'];

            if (!$response->hasHeader($header)) {
                throw new RuntimeException(sprintf(
                    'The "%s" header is not present in all of the last %d response headers.',
                    $header,
                    $num
                ));
            }

            $values[] = $response->getHeaderLine($header);
        }

        if ($unique) {
            Assertion::count(
                $uniqueValues = array_unique($values),
                $num,
                sprintf(
                    'Expected %d unique values, got %d. Values compared: %s',
                    $num,
                    count($uniqueValues),
                    print_r($values, true)
                )
            );
        } else {
            Assertion::count(
                array_unique($values),
                1,
                sprintf(
                    'Expected all values to be the same. Values compared: %s',
                    print_r($values, true)
                )
            );
        }

        return $this;
    }

    /**
     * Set a query parameter to the image identifier of a specific image already added to Imbo
     *
     * @param string $param Name of the query parameter
     * @param string $path Path of a local image that exists in Imbo
     * @throws InvalidArgumentException
     * @return self
     *
     * @Given the query string parameter :param is set to the image identifier of :path
     */
    public function setRequestParameterToImageIdentifier($param, $path) {
        if (!isset($this->imageIdentifiers[$path])) {
            throw new InvalidArgumentException(sprintf(
                'No image identifier exists for image: "%s".',
                $path
            ));
        }

        return $this->setRequestQueryParameter($param, $this->imageIdentifiers[$path]);
    }

    /**
     * Perform a series of requests
     *
     * The $table parameter must be a table with the following columns:
     *
     * - (string) path, required: The path to request. Some special values can be used for dynamic
     *                            requests:
     *                              - "previously added image": Request the previously added image
     * - (string) method: The HTTP method to use, defaults to GET
     * - (string) extension: Used to force a specific image type, for instance "jpg"
     * - (string) transformation: An image transformation to add to the request
     * - (string) sign request: Set to "yes" to sign the request. Remember to specify public and
     *                          private keys prior to running the request
     *
     * @param TableNode $table Information about the requests to make
     * @throws InvalidArgumentException
     * @return self
     *
     * @When I request:
     */
    public function requestPaths(TableNode $table) {
        foreach ($table as $row) {
            foreach (['path', 'method'] as $key) {
                if (!isset($row[$key])) {
                    throw new InvalidArgumentException(sprintf('Table is missing "%s" key.', $key));
                }
            }

            $method = $row['method'] ?: 'GET';
            $path = $row['path'];

            if (!empty($row['transformation'])) {
                $this->applyTransformation($row['transformation']);
            }

            if (!empty($row['sign request']) && $row['sign request'] === 'yes') {
                $this->signRequest();
            }

            if ($path === 'previously added image') {
                $extension = isset($row['extension']) ? $row['extension'] : null;
                $this->requestPreviouslyAddedImage($method, $extension);
            } else {
                $this->requestPath($path, $method);
            }
        }

        return $this;
    }

    /**
     * Match a series of responses against a data set represented as a TableNode
     *
     * This step can be used to match requests typically made with the `@When I request:` step.
     *
     * The $table parameter must be a table with the following columns:
     *
     * - (int) response, required: The number of the request, 1-based index where the lowest number
     *                             is the oldest response.
     * - (string) status line: Match the status line
     * - (string) header name: Match a header (used with `header value`)
     * - (string) header value: Match a header (used with `header name`)
     * - (string) checksum: Match the MD5 checksum of the response body with this value
     * - (int) image width: Match the width of the image in the request with this value
     * - (int) image height: Match the height of the image in the reqeust with this value
     *
     * @param int $num The number of responses to match
     * @param TableNode $table The data to match against
     * @throws RuntimeException|InvalidArgumentException|OutOfBoundsException
     * @return self
     *
     * @Then the last :num responses match:
     */
    public function theLastResponsesMatch($num, TableNode $table) {
        $num = (int) $num;

        if (count($this->history) < $num) {
            throw new RuntimeException(sprintf(
                'Not enough requests in the history. Needs at least %d, actual: %d.',
                $num,
                count($this->history))
            );
        }

        // First, reverse the history and slice $num elements off. Then reverse those, and pick
        // only the response elements from the resulting array.
        $reversedOrder = array_reverse($this->history);
        $responses = array_column(array_reverse(array_slice($reversedOrder, 0, $num)), 'response');

        foreach ($table as $row) {
            if (!isset($row['response']) || empty($row['response'])) {
                throw new InvalidArgumentException(
                    'Each row must refer to a response by using the "response" column.'
                );
            }

            $index = $row['response'] - 1;

            if (!isset($responses[$index])) {
                throw new OutOfBoundsException(sprintf(
                    'Invalid response number: %d.',
                    $row['response']
                ));
            }

            $response = $responses[$index];

            if (!empty($row['status line'])) {
                $actualStatusLine = sprintf(
                    '%d %s',
                    $response->getStatusCode(),
                    $response->getReasonPhrase()
                );

                Assertion::same(
                    $row['status line'],
                    $actualStatusLine, sprintf(
                        'Incorrect status line in response %d, expected "%s", got: "%s".',
                        $row['response'],
                        $row['status line'],
                        $actualStatusLine
                    )
                );
            }

            if (!empty($row['header name']) && !empty($row['header value'])) {
                Assertion::true(
                    $response->hasHeader($row['header name']),
                    sprintf(
                        'Expected response %d to have the "%s" header, but it does not.',
                        $row['response'],
                        $row['header name']
                    )
                );

                Assertion::same(
                    $row['header value'],
                    $headerValue = $response->getHeaderLine($row['header name']),
                    sprintf(
                        'Incorrect "%s" header value in response %d, expected "%s", got: "%s".',
                        $row['header name'],
                        $row['response'],
                        $row['header value'],
                        $headerValue
                    )
                );
            }

            if (!empty($row['checksum'])) {
                Assertion::same(
                    $row['checksum'],
                    $checksum = md5((string) $response->getBody()),
                    sprintf(
                        'Incorrect checksum in response %d, expected "%s", got: "%s".',
                        $row['response'],
                        $row['checksum'],
                        $checksum
                    )
                );
            }

            if (!empty($row['image width']) || !empty($row['image height'])) {
                list($actualWidth, $actualHeight) = getimagesizefromstring((string) $this->response->getBody());

                if (!empty($row['image width'])) {
                    Assertion::same(
                        (int) $row['image width'],
                        $actualWidth,
                        sprintf(
                            'Expected image in response %d to be %d pixel(s) wide, actual: %d.',
                            $row['response'],
                            $row['image width'],
                            $actualWidth
                        )
                    );
                }

                if (!empty($row['image height'])) {
                    Assertion::same(
                        (int) $row['image height'],
                        $actualHeight,
                        sprintf(
                            'Expected image in response %d to be %d pixel(s) high, actual: %d.',
                            $row['response'],
                            $row['image height'],
                            $actualHeight
                        )
                    );
                }
            }
        }

        return $this;
    }



























    /**
     * @Given /^I do not specify a public and private key$/
     */
    public function removeClientAuth() {
        $this->publicKey = null;
        $this->privateKey = null;
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
