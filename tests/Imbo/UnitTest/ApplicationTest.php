<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest;

use Imbo\Application,
    Imbo\Database,
    Imbo\Storage,
    Imbo\Container,
    Imbo\EventListener;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
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
                    'routes' => array(),
                    'resources' => array(),
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
                    'routes' => array(),
                    'resources' => array(),
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
            'routes' => array(),
            'resources' => array(),
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
            'routes' => array(),
            'resources' => array(),
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
                    'routes' => array(),
                    'resources' => array(),
                ),
            ),
            array(
                array(
                    'eventListeners' => array('custom' => array('listener' => 'somefunction')),
                    'imageTransformations' => array(),
                    'routes' => array(),
                    'resources' => array(),
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
}
