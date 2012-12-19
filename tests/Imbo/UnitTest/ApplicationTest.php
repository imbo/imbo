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
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\UnitTest;

use Imbo\Application,
    Imbo\Database,
    Imbo\Storage,
    Imbo\Container,
    Imbo\EventListener;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Application
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Application
     */
    private $application;

    /**
     * @var array
     */
    private $config = array();

    /**
     * Set up the application
     */
    public function setUp() {
        $this->application = new Application();
    }

    /**
     * Tear down the application
     */
    public function tearDown() {
        $this->application = null;
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Application has not been bootstrapped
     * @covers Imbo\Application::run
     */
    public function testWillThrowExceptionWhenNotBootstrapped() {
        $this->application->run();
    }

    /**
     * Fetch different configs
     *
     * @return array[]
     */
    public function getConfigs() {
        return array(
            array(require __DIR__ . '/../../../config/config.default.php'),
            array(
                array(
                    'database' => new Database\MongoDB(),
                    'storage' => new Storage\Filesystem(array('dataDir' => '/tmp')),
                    'imageTransformations' => array(),
                    'eventListeners' => array(
                        'auth' => new EventListener\Authenticate(),
                    ),
                ),
            ),
            array(
                array(
                    'database' => function() { return new Database\MongoDB(); },
                    'storage' => function() { return new Storage\Filesystem(array('dataDir' => '/tmp')); },
                    'imageTransformations' => array(),
                    'eventListeners' => array(
                        'auth' => function() { return new EventListener\Authenticate(); },
                        'custom' => array(
                            'callback' => function($event) {},
                            'events' => array('response.send'),
                            'priority' => 20,
                        ),
                        'custom2' => array(
                            'callback' => function($event) {},
                            'events' => array('response.send' => 20, 'response.send' => -20),
                        ),
                        'custom3' => null,
                        'custom4' => array(
                            'listener' => new EventListener\Authenticate(),
                        ),
                        'custom5' => array(
                            'listener' => function() { return new EventListener\Authenticate(); },
                        ),
                        'custom4' => array(
                            'listener' => new EventListener\Authenticate(),
                            'publicKeys' => array('include' => array('someuser')),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider getConfigs
     * @covers Imbo\Application::bootstrap
     */
    public function testCanBootstrapDefaultConfiguration(array $config) {
        $container = new Container();

        $this->application->bootstrap($config, $container);
        $this->assertSame($config, $container->get('config'));
        $this->assertInstanceOf('Imbo\Database\DatabaseInterface', $container->get('database'));
        $this->assertInstanceOf('Imbo\Storage\StorageInterface', $container->get('storage'));
        $this->assertInstanceOf('Imbo\EventListener\ImageTransformer', $container->get('imageTransformer'));
        $this->assertInstanceOf('Imbo\EventManager\EventManager', $container->get('eventManager'));
        $this->assertInstanceOf('Imbo\Resource\Images\Query', $container->get('imagesQuery'));
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid database adapter
     * @expectedExceptionCode 500
     * @covers Imbo\Application::bootstrap
     */
    public function testThrowsExceptionWhenConfigurationHasInvalidDatabaseAdapter() {
        $container = new Container();
        $this->application->bootstrap(array(
            'database' => function() { return new \stdClass(); },
        ), $container);
        $container->get('database');
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid storage adapter
     * @expectedExceptionCode 500
     * @covers Imbo\Application::bootstrap
     */
    public function testThrowsExceptionWhenConfigurationHasInvalidStorageAdapter() {
        $container = new Container();
        $this->application->bootstrap(array(
            'storage' => function() { return new \stdClass(); },
        ), $container);
        $container->get('storage');
    }

    /**
     * Get invalid event listener configuration
     *
     * @return array[]
     */
    public function getInvalidEventListenerConfig() {
        return array(
            array(
                array(
                    'eventListeners' => array('foo' => 'bar'),
                    'imageTransformations' => array(),
                ),
            ),
            array(
                array(
                    'eventListeners' => array('custom' => array('listener' => 'somefunction')),
                    'imageTransformations' => array(),
                ),
            ),
        );
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid event listener definition
     * @expectedExceptionCode 500
     * @dataProvider getInvalidEventListenerConfig
     * @covers Imbo\Application::bootstrap
     */
    public function testThrowsExceptionWhenConfigurationHasInvalidEventListener($config) {
        $container = new Container();
        $this->application->bootstrap($config, $container);
        $container->get('eventManager');
    }

    /**
     * Fetch different combinations of the $_SERVER var
     *
     * @return array[]
     */
    public function getServerVars() {
        return array(
            array(
                array (
                    'HTTP_HOST' => 'imbo',
                    'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'SERVER_PORT' => '80',
                    'DOCUMENT_ROOT' => '/home/christer/dev/imbo/public',
                    'SCRIPT_FILENAME' => '/home/christer/dev/imbo/public/index.php',
                    'REQUEST_METHOD' => 'GET',
                    'REQUEST_URI' => '/status.json',
                ),
                '{"date":".*?","database":true,"storage":true}',
            ),
            array(
                array (
                    'HTTP_HOST' => 'imbo',
                    'HTTP_ACCEPT' => 'text/html',
                    'SERVER_PORT' => '80',
                    'DOCUMENT_ROOT' => '/home/christer/dev/imbo/public',
                    'SCRIPT_FILENAME' => '/home/christer/dev/imbo/public/index.php',
                    'REQUEST_METHOD' => 'GET',
                    'REQUEST_URI' => '/status',
                ),
                '<!DOCTYPE html>.*?<html.*?>.*?<head>.*?</head>.*?<body>.*?<h1>Status</h1>.*?<dl>.*?</dl>.*?</body>.*?</html>',
            ),
            array(
                array (
                    'HTTP_HOST' => 'imbo',
                    'HTTP_ACCEPT' => 'application/xml',
                    'SERVER_PORT' => '80',
                    'DOCUMENT_ROOT' => '/home/christer/dev/imbo/public',
                    'SCRIPT_FILENAME' => '/home/christer/dev/imbo/public/index.php',
                    'REQUEST_METHOD' => 'GET',
                    'REQUEST_URI' => '/status',
                ),
                '<\?xml version="1.0" encoding="UTF-8"\?>.*?<imbo>.*?</imbo>',
            ),
            array(
                array (
                    'HTTP_HOST' => 'imbo',
                    'HTTP_ACCEPT' => 'application/json',
                    'SERVER_PORT' => '80',
                    'DOCUMENT_ROOT' => '/home/christer/dev/imbo/public',
                    'SCRIPT_FILENAME' => '/home/christer/dev/imbo/public/index.php',
                    'REQUEST_METHOD' => 'GET',
                    'REQUEST_URI' => '/users/someuserthatdoesnotexist',
                ),
                '{"error":{"code":404,"message":"Unknown Public Key","date":".*?","imboErrorCode":100}}',
            ),
            array(
                array (
                    'HTTP_HOST' => 'imbo',
                    'HTTP_ACCEPT' => 'application/json',
                    'SERVER_PORT' => '80',
                    'DOCUMENT_ROOT' => '/home/christer/dev/imbo/public',
                    'SCRIPT_FILENAME' => '/home/christer/dev/imbo/public/index.php',
                    'REQUEST_METHOD' => 'DELETE',
                    'REQUEST_URI' => '/status',
                ),
                '{"error":{"code":405,"message":"Method not allowed","date":".*?","imboErrorCode":0}}',
            ),
        );
    }

    /**
     * @dataProvider getServerVars
     * @covers Imbo\Application::run
     * @covers Imbo\Application::bootstrap
     */
    public function testApplicationCanRun($server, $output) {
        $_GET = array();
        $_POST = array();
        $_SERVER = $server;

        $this->expectOutputRegEx('#^' . $output . '$#ims');
        $this->application->bootstrap(require __DIR__ . '/../../../config/config.default.php')->run();
    }
}
