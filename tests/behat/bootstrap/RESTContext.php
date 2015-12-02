<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

use Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException,
    Behat\Behat\Event\SuiteEvent,
    Behat\Gherkin\Node\PyStringNode,
    Guzzle\Http\Client,
    Guzzle\Http\Message\Request,
    Guzzle\Http\Message\Response;

// Require PHPUnit assertions manually since we're using it outside of PHPUnit
require __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * REST context for Behat tests
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Functional tests
 */
class RESTContext extends BehatContext {
    /**
     * Guzzle client used to make requests against the httpd
     *
     * @var Client
     */
    protected $client;

    /**
     * The current response objects used by the client (populated when the request is sent)
     *
     * @var Response[]
     */
    protected $responses = [];

    /**
     * Headers for the request
     *
     * @var array
     */
    protected $requestHeaders = [];

    /**
     * Query parameters for the request
     *
     * @var array
     */
    protected $queryParams = [];

    /**
     * Optional request body to add to the request
     *
     * @var string
     */
    protected $requestBody;

    /**
     * The previously requested path
     *
     * @var string
     */
    private $prevRequestedPath;

    /**
     * The current test session id
     *
     * @var string
     */
    private static $testSessionId;

    /**
     * Parameters from the configuration
     *
     * @var array
     */
    private $params;

    /**
     * Class constructor
     *
     * @param array $parameters Context parameters
     */
    public function __construct(array $parameters) {
        $this->params = $parameters;
        $this->createClient();
    }

    /**
     * Returns a list of HTTP verbs that we need to do an override of in order
     * to bypass limitations in the built-in PHP HTTP server.
     *
     * The returned list contains the verb to use override for, and what verb
     * to use when overriding. For instance POST could be used when we want to
     * perform a SEARCH request as a payload is expected while GET could be used
     * if we want to test something using the LINK method.
     */
    private function getOverrideVerbs() {
        return [
            'SEARCH' => 'POST'
        ];
    }

    /**
     * Create a new HTTP client
     */
    private function createClient() {
        $this->client = new Client($this->params['url']);

        $defaultHeaders = [
            'X-Test-Session-Id' => self::$testSessionId,
        ];

        if ($this->params['enableCodeCoverage']) {
            $defaultHeaders['X-Enable-Coverage'] = 1;
        }

        $this->client->setDefaultHeaders($defaultHeaders);
    }

    /**
     * Start up the built in httpd in php-5.4
     *
     * @BeforeSuite
     */
    public static function setUp(SuiteEvent $event) {
        $params = $event->getContextParameters();
        $url = parse_url($params['url']);
        $port = !empty($url['port']) ? $url['port'] : 80;

        if (self::canConnectToHttpd($url['host'], $port)) {
            throw new RuntimeException('Something is already running on ' . $params['url'] . '. Aborting tests.');
        }

        $pid = self::startBuiltInHttpd(
            $url['host'],
            $port,
            $params['documentRoot'],
            $params['router'],
            $params['httpdLog']
        );

        if (!$pid) {
            // Could not start the httpd for some reason
            throw new RuntimeException('Could not start the web server');
        }

        // Try to connect
        $start = microtime(true);
        $connected = false;

        while (microtime(true) - $start <= (int) $params['timeout']) {
            if (self::canConnectToHttpd($url['host'], $port)) {
                $connected = true;
                break;
            }
        }

        if (!$connected) {
            throw new RuntimeException(
                sprintf(
                    'Could not connect to the web server within the given timeframe (%d second(s))',
                    $params['timeout']
                )
            );
        }

        // Register a shutdown function that will automatically shut down the httpd
        register_shutdown_function(function() use ($pid) {
            exec('kill ' . $pid);
        });

        self::$testSessionId = uniqid('', true);
    }

    /**
     * Collect code coverage after the suite has been run
     *
     * @AfterSuite
     */
    public static function tearDown(SuiteEvent $event) {
        $parameters = $event->getContextParameters();

        if ($parameters['enableCodeCoverage']) {
            $client = new Client($parameters['url']);
            $response = $client->get('/', [
                'X-Enable-Coverage' => 1,
                'X-Test-Session-Id' => self::$testSessionId,
                'X-Collect-Coverage' => 1,
            ])->send();

            $data = unserialize((string) $response->getBody());

            $filter = new PHP_CodeCoverage_Filter();

            foreach ($parameters['whitelist'] as $dir) {
                $filter->addDirectoryToWhitelist($dir);
            }

            $coverage = new PHP_CodeCoverage(null, $filter);
            $coverage->append($data, 'behat-suite');

            $report = new PHP_CodeCoverage_Report_HTML();
            $report->process($coverage, $parameters['coveragePath']);
        }
    }

    /**
     * Set method override header used to fake non-standard HTTP verbs
     *
     * @param string $method Override method
     */
    public function setOverrideMethodHeader($method) {
        $this->setRequestHeader('X-Http-Method-Override', $method);
    }

    /**
     * @Given /^the "([^"]*)" request header is "([^"]*)"$/
     */
    public function setRequestHeader($header, $value) {
        $this->requestHeaders[$header] = $value;
    }

     /**
     * @Given /^I append a query string parameter, "([^"]*)" with the value "([^"]*)"$/
     */
    public function appendQueryStringParameter($queryParam, $value) {
        $this->queryParams[] = $queryParam . '=' . $value;
    }

    /**
     * @When /^I request "([^"]*)" with the given query string$/
     */
    public function performRequestWithGivenQueryString($path) {
        $this->request($path . '?' . implode('&', $this->queryParams));
    }

    /**
     * @When /^I request "([^"]*)"(?: using HTTP "([^"]*)")?$/
     */
    public function request($path, $method = 'GET') {
        $this->prevRequestedPath = $path;

        if (empty($this->requestHeaders['Accept'])) {
            $this->requestHeaders['Accept'] = 'application/json';
        }

        // Add override method header if specified in the list of override verbs
        if (array_key_exists($method, $this->getOverrideVerbs())) {
            $this->setOverrideMethodHeader($method);
            $method = $this->getOverrideVerbs()[$method];
        }

        $request = $this->client->createRequest($method, $path, $this->requestHeaders);

        if ($this->requestBody) {
            $request->setBody($this->requestBody);
            $this->requestBody = null;
        }

        try {
            $response = $request->send();
        } catch (Exception $e) {
            $response = $e->getResponse();
        }

        $this->responses[] = $response;
    }

    /**
     * @Given /^make the same request using HTTP "([^"]*)"$/
     */
    public function makeSameRequest($method) {
        $this->appendAccessToken();
        $this->request($this->prevRequestedPath, $method);
    }

    /**
     * @Then /^the following response headers should be the same:$/
     */
    public function assertEqualResponseHeaders(PyStringNode $list) {
        if (count($this->responses) < 2) {
            throw new \Exception('Need more than one response');
        }

        $headersToMatch = $list->getLines();
        $numResponses = count($this->responses);

        $latestResponse = $this->responses[$numResponses - 1];
        $previousResponse = $this->responses[$numResponses - 2];

        foreach ($headersToMatch as $header) {
            assertTrue($latestResponse->hasHeader($header), 'Header "' . $header . '" is missing');
            assertTrue($previousResponse->hasHeader($header), 'Header "' . $header . '" is missing');
            assertSame((string) $latestResponse->getHeader($header), (string) $previousResponse->getHeader($header), 'Header "' . $header . '" does not match');
        }
    }

    /**
     * @Given /^the following response headers should not be present:$/
     */
    public function assertMissingHeaders(PyStringNode $list) {
        $headers = $list->getLines();

        foreach ($headers as $header) {
            assertFalse($this->getLastResponse()->hasHeader($header), 'Header "' . $header . '" should not be present');
        }
    }

    /**
     * @Given /^the following response headers should be present:$/
     */
    public function assertExistingHeaders(PyStringNode $list) {
        $headers = $list->getLines();

        foreach ($headers as $header) {
            assertTrue($this->getLastResponse()->hasHeader($header), 'Header "' . $header . '" should be present');
        }
    }

    /**
     * @Given /^the response is (not )?cacheable$/
     */
    public function assertResponseIsCacheable($cacheable = true) {
        if ($cacheable !== true) {
            $cacheable = false;
        }

        assertSame($cacheable, $this->getLastResponse()->canCache());
    }

    /**
     * @Given /^the response has a max age of (\d+) seconds$/
     */
    public function assertMaxAge($seconds) {
        $cacheControl = $this->getPreviousCacheControlHeader();
        $maxAge = $cacheControl->getDirective('max-age');

        if ($maxAge === null) {
            throw new \Exception('No `max-age` directive present in `cache-control`');
        }

        assertSame((int) $seconds, (int) $maxAge);
    }

    /**
     * @Given /^the response (does not )?(?:has|have) a must-revalidate directive$/
     */
    public function assertMustRevalidate($doesNotHave = false) {
        $cacheControl = $this->getPreviousCacheControlHeader();

        if ($doesNotHave) {
            assertFalse($cacheControl->hasDirective('must-revalidate'));
        } else {
            assertTrue($cacheControl->hasDirective('must-revalidate'));
        }
    }

    /**
     * @Then /^I should get a response with "([^"]*)"$/
     */
    public function assertResponseStatus($status) {
        $response = $this->getLastResponse();
        $actual = $response->getStatusCode() . ' ' . $response->getReasonPhrase();
        assertSame($status, $actual, 'Expected "' . $status . '", got "' . $actual . '"');
    }

    /**
     * @Given /^the "([^"]*)" response header (is|contains|matches) "(.*?)"$/
     */
    public function assertResponseHeader($header, $match, $value) {
        $response = $this->getLastResponse();
        $actual = (string) $response->getHeader($header);

        if ($match === 'is') {
            assertSame($value, $actual, 'Expected "' . $value . '", got "' . $actual . '"');
        } else if ($match === 'matches') {
            assertRegExp('#^' . $value . '$#', $actual, $actual . ' does not match ' . $value);
        } else {
            assertContains($value, $actual, $actual . ' does not contain ' . $value);
        }
    }

    /**
     * @Then /^the "([^"]*)" response header does not exist$/
     */
    public function assertHeaderDoesNotExist($header) {
        $response = $this->getLastResponse();
        assertFalse($response->hasHeader($header), 'The "' . $header . '" response header should not exist');
    }

    /**
     * @Given /^I attach "([^"]*)" to the request body$/
     */
    public function attachFileToRequestBody($path) {
        if (!$fullPath = realpath($path)) {
            throw new RuntimeException('Path "' . $path . '" is invalid');
        }

        $this->requestBody = file_get_contents($fullPath);
    }

    /**
     * @Given /^the request body contains:$/
     */
    public function setRequestBody(PyStringNode $body) {
        $this->requestBody = (string) $body;
    }

    /**
     * @Given /^the response body should be empty$/
     */
    public function assertEmptyResponseBody() {
        $response = $this->getLastResponse();
        assertEmpty((string) $response->getBody());
    }

    /**
     * @Given /^the response body (contains|is|matches):$/
     */
    public function assertResponseBody($match, PyStringNode $expected) {
        $expected = trim((string) $expected);

        $actual = trim((string) $this->getLastResponse()->getBody());

        if ($match === 'is') {
            assertSame($expected, $actual, sprintf('Expected %s, got %s', $expected, $actual));
        } else if ($match === 'matches') {
            assertRegExp($expected, $actual);
        } else {
            assertContains($expected, $actual);
        }

    }

    /**
     * @Given /^the response body length is "([^"]*)"$/
     */
    public function assertResponseBodyLength($length) {
        assertSame(strlen((string) $this->getLastResponse()->getBody()), (int) $length);
    }

    /**
     * @Then /^the "([^"]*)" response header is not the same for any of the requests$/
     */
    public function assertHeaderNotSameForPreviousRequests($header) {
        $responses = array_slice($this->responses, 1);

        $headerValues = array_map(function($response) use ($header) {
            return (string) $response->getHeader($header);
        }, $responses);

        $totalValues = count($headerValues);
        $uniqueValues = count(array_unique($headerValues));

        assertSame($totalValues, $uniqueValues, 'Only ' . $uniqueValues . ' header(s) were unique, out of ' . $totalValues . ' total');
    }

    /**
     * See if we have an httpd we can connect to
     *
     * @param string $host The hostname to connect to
     * @param int $port The port to use
     * @return boolean
     */
    private static function canConnectToHttpd($host, $port) {
        set_error_handler(function() { return true; });
        $sp = fsockopen($host, $port);
        restore_error_handler();

        if ($sp === false) {
            return false;
        }

        fclose($sp);

        return true;
    }

    /**
     * Start the built in httpd in php-5.4
     *
     * @param string $host The hostname to use
     * @param int $port The port to use
     * @param string $documentRoot The document root
     * @param string $router Path to an optional router
     * @param string $httpdLog Path the httpd log should be written to
     * @return int Returns the PID of the httpd
     * @throws RuntimeException
     */
    private static function startBuiltInHttpd($host, $port, $documentRoot, $router, $httpdLog) {
        $logPath = dirname($httpdLog);

        if (!is_dir($logPath)) {
            mkdir($logPath, 0777, true);
        }

        $command = sprintf('php -S %s:%d -t %s %s >%s 2>&1 & echo $!',
                            $host,
                            $port,
                            $documentRoot,
                            $router,
                            $httpdLog);

        $output = [];
        exec($command, $output);

        return (int) $output[0];
    }

    /**
     * Get the response to the last request made by the Guzzle client
     *
     * @return Response
     */
    protected function getLastResponse() {
        return $this->responses[count($this->responses) - 1];
    }

    /**
     * Add a request header for the next request
     *
     * @param string $key The name of the header
     * @param mixed $value The value of the header
     */
    protected function addHeaderToNextRequest($key, $value) {
        $this->client->getEventDispatcher()->addListener('request.before_send', function($event) use ($key, $value) {
            $event['request']->setHeader($key, $value);
        });
    }

    /**
     * Get the cache-control header for the previous response
     *
     * @throws Exception If no cache-control header is present in the previous response
     * @return Guzzle\Http\Message\Header\CacheControl The Cache-Control header for the previous response
     */
    protected function getPreviousCacheControlHeader() {
        $cacheControl = $this->getLastResponse()->getHeader('Cache-Control');
        if (!$cacheControl) {
            throw new \Exception('No `cache-control` header present');
        }

        return $cacheControl;
    }
}
