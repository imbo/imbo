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

// PHPUnit related classes
require 'PHPUnit/Framework/Assert/Functions.php';
require 'PHP/CodeCoverage.php';
require 'PHP/CodeCoverage/Filter.php';
require 'PHP/CodeCoverage/Report/HTML.php';

/**
 * REST context for Behat tests
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Functional tests
 */
class RESTContext extends BehatContext {
    /**
     * Pid for the built-in httpd in php-5.4
     *
     * @var int
     */
    private static $pid;

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
    protected $responses = array();

    /**
     * Headers for the request
     *
     * @var array
     */
    protected $requestHeaders = array();

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
     * The current coverage session id
     *
     * @var string
     */
    private static $coverageSession;

    /**
     * Class constructor
     *
     * @param array $parameters Context parameters
     */
    public function __construct(array $parameters) {
        $this->client = new Client($parameters['url']);

        if ($parameters['enableCodeCoverage']) {
            $this->client->setDefaultHeaders(array(
                'X-Enable-Coverage' => 1,
                'X-Coverage-Session' => self::$coverageSession,
            ));
        }
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

        self::$pid = self::startBuiltInHttpd(
            $url['host'],
            $port,
            $params['documentRoot'],
            $params['router']
        );

        sleep(1);

        if (!self::canConnectToHttpd($url['host'], $port)) {
            throw new RuntimeException('Could not start the built in httpd');
        }

        self::$coverageSession = uniqid('', true);
    }

    /**
     * Kill the httpd process if it has been started
     *
     * @AfterSuite
     */
    public static function tearDown(SuiteEvent $event) {
        $parameters = $event->getContextParameters();

        if ($parameters['enableCodeCoverage']) {
            $client = new Client($parameters['url']);
            $response = $client->get('/', array(
                'X-Enable-Coverage' => 1,
                'X-Coverage-Session' => self::$coverageSession,
                'X-Collect-Coverage' => 1,
            ))->send();

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

        if (self::$pid) {
            exec('kill ' . self::$pid);
            self::$pid = null;
        }
    }

    /**
     * @Given /^the "([^"]*)" request header is "([^"]*)"$/
     */
    public function setRequestHeader($header, $value) {
        $this->requestHeaders[$header] = $value;
    }

    /**
     * @When /^I request "([^"]*)"(?: using HTTP "([^"]*)")?$/
     */
    public function request($path, $method = 'GET') {
        $this->prevRequestedPath = $path;

        if (empty($this->requestHeaders['Accept'])) {
            $this->requestHeaders['Accept'] = 'application/json';
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
            assertFalse($this->responses[count($this->responses) - 1]->hasHeader($header), 'Header "' . $header . '" should not be present');
        }
    }

    /**
     * @Given /^the response is (not )?cacheable$/
     */
    public function assertResponseIsCacheable($cacheable = true) {
        if ($cacheable !== true) {
            $cacheable = false;
        }

        assertSame($cacheable, $this->responses[count($this->responses) - 1]->canCache());
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
     * @Given /^the "([^"]*)" response header is "([^"]*)"$/
     */
    public function assertResponseHeader($header, $value) {
        $response = $this->getLastResponse();
        $actual = (string) $response->getHeader($header);
        assertSame($value, $actual, 'Expected "' . $value . '", got "' . $actual . '"');
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
     * @return int Returns the PID of the httpd
     * @throws RuntimeException
     */
    private static function startBuiltInHttpd($host, $port, $documentRoot, $router = null) {
        $command = sprintf('php -S %s:%d -t %s %s >/dev/null 2>&1 & echo $!',
                            $host,
                            $port,
                            $documentRoot,
                            $router);

        $output = array();
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
}
