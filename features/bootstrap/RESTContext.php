<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package Tests/Behat
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
            throw new RuntimeException('Requires php-5.4 to run');
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
