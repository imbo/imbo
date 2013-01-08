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
    Guzzle\Http\Client,
    Guzzle\Http\Message\Request,
    Guzzle\Http\Message\Response;

// PHPUnit assert functions
require 'PHPUnit/Framework/Assert/Functions.php';

/**
 * REST context for Behat tests
 *
 * @package Tests/Behat
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class RESTContext extends BehatContext {
    /**
     * Pid for the optional built-in httpd in php-5.4 that will be used if no other httpd responds
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
     * The current response object used by the client (populated when the request is sent)
     *
     * @var Response
     */
    protected $response;

    /**
     * Headers for the request
     *
     * @var array
     */
    protected $requestHeaders = array();

    /**
     * Class constructor
     *
     * @param array $parameters Context parameters
     */
    public function __construct(array $parameters) {
        $this->client = new Client($parameters['url']);
    }

    /**
     * Try to connect to the url specified in behat.yml. If not successful, start up the built in
     * httpd in php-5.4 and try to connect to that instead.
     *
     * @BeforeSuite
     */
    public static function setUp(SuiteEvent $event) {
        $params = $event->getContextParameters();
        $url = parse_url($params['url']);
        $port = !empty($url['port']) ? $url['port'] : 80;

        if (!self::canConnectToHttpd($url['host'], $port)) {
            // No connection. Let's try and fire up the built in httpd (requires php-5.4)
            self::$pid = self::startBuiltInHttpd(
                $url['host'],
                $url['port'],
                $params['documentRoot'],
                $params['router']
            );

            sleep(1);

            if (!self::canConnectToHttpd($url['host'], $port)) {
                throw new RuntimeException('Could not start the built in httpd');
            }
        }
    }

    /**
     * Kill the httpd process if it has been started
     *
     * @AfterSuite
     */
    public static function tearDown(SuiteEvent $event) {
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
        $request = $this->client->createRequest($method, $path, $this->requestHeaders);

        try {
            $this->response = $request->send();
        } catch (Exception $e) {
            $this->response = $e->getResponse();
        }
    }

    /**
     * @Then /^I should get a response with "([^"]*)"$/
     */
    public function assertResponseStatus($status) {
        assertSame(
            $status,
            $this->response->getStatusCode() . ' ' . $this->response->getReasonPhrase()
        );
    }

    /**
     * @Given /^the "([^"]*)" response header is "([^"]*)"$/
     */
     public function assertResponseHeader($header, $value) {
         assertSame($value, (string) $this->response->getHeader($header));
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
        if (version_compare(PHP_VERSION, '5.4.0') < 0) {
            echo 'Requires php-5.4 to run' . PHP_EOL;
            exit(0);
        }

        $command = sprintf('php -S %s:%d -t %s %s >/dev/null 2>&1 & echo $!',
                            $host,
                            $port,
                            $documentRoot,
                            $router);

        $output = array();
        exec($command, $output);

        return (int) $output[0];
    }
}
