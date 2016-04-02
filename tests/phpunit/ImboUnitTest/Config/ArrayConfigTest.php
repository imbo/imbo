<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Config;

use Imbo\Config\ArrayConfig;

/**
 * @covers Imbo\Config\ArrayConfig
 * @group unit
 * @group config
 */
class ArrayConfigTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var PhpFileConfig
     */
    private $config;

    public function setUp() {
        $this->config = new ArrayConfig([
            'accessControl' => ''
        ]);
    }

    public function tearDown() {
        $this->config = null;
    }

    /**
     * @covers Imbo\Config\ArrayConfig::getAccessControl
     */
    public function testGetAccessControl() {
        $accessControl = $this->getMock('Imbo\Auth\AccessControl\Adapter\AdapterInterface');

        $config = new ArrayConfig([
            'accessControl' => $accessControl
        ]);

        $this->assertSame($accessControl, $config->getAccessControl());
    }

    /**
     * @covers Imbo\Config\ArrayConfig::getDatabase
     */
    public function testGetDatabase() {
        $database = $this->getMock('Imbo\Database\DatabaseInterface');

        $config = new ArrayConfig([
            'database' => $database
        ]);

        $this->assertSame($database, $config->getDatabase());
    }

    /**
     * @covers Imbo\Config\ArrayConfig::getStorage
     */
    public function testGetStorage() {
        $storage = $this->getMock('Imbo\Storage\StorageInterface');

        $config = new ArrayConfig([
            'storage' => $storage
        ]);

        $this->assertSame($storage, $config->getStorage());
    }

    /**
     * @covers Imbo\Config\ArrayConfig::getImageIdentifierGenerator
     */
    public function testGetimageIdentifierGenerator() {
        $imageIdentifierGenerator = $this->getMock('Imbo\Image\Identifier\Generator\GeneratorInterface');

        $config = new ArrayConfig([
            'imageIdentifierGenerator' => $imageIdentifierGenerator
        ]);

        $this->assertSame($imageIdentifierGenerator, $config->getImageIdentifierGenerator());
    }

    /**
     * @covers Imbo\Config\ArrayConfig::getContentNegotiateImages
     */
    public function testGetContentNegotiateImages() {
        $contentNegotiateImages = false;

        $config = new ArrayConfig([
            'contentNegotiateImages' => $contentNegotiateImages
        ]);

        $this->assertSame($contentNegotiateImages, $config->getContentNegotiateImages());
    }

    /**
     * @covers Imbo\Config\ArrayConfig::getHttpCacheHeaders
     */
    public function testGetHttpCacheHeaders() {
        $httpCacheHeaders = false;

        $config = new ArrayConfig([
            'httpCacheHeaders' => $httpCacheHeaders
        ]);

        $this->assertSame($httpCacheHeaders, $config->getHttpCacheHeaders());
    }

    /**
     * @covers Imbo\Config\ArrayConfig::getAuthentication
     */
    public function testGetAuthentication() {
        $authentication = [];

        $config = new ArrayConfig([
            'authentication' => $authentication
        ]);

        $this->assertSame($authentication, $config->getAuthentication());
    }

    /**
     * @covers Imbo\Config\ArrayConfig::getEventListeners
     */
    public function testGetEventListeners() {
        $eventListeners = [];

        $config = new ArrayConfig([
            'eventListeners' => $eventListeners
        ]);

        $this->assertSame($eventListeners, $config->getEventListeners());
    }

    /**
     * @covers Imbo\Config\ArrayConfig::getEventListenerInitializers
     */
    public function testGetEventListenerInitializers() {
        $eventListenerInitializers = [];

        $config = new ArrayConfig([
            'eventListenerInitializers' => $eventListenerInitializers
        ]);

        $this->assertSame($eventListenerInitializers, $config->getEventListenerInitializers());
    }

    /**
     * @covers Imbo\Config\ArrayConfig::getTransformationPresets
     */
    public function testGetTransformationPresets() {
        $transformationPresets = [];

        $config = new ArrayConfig([
            'transformationPresets' => $transformationPresets
        ]);

        $this->assertSame($transformationPresets, $config->getTransformationPresets());
    }

    /**
     * @covers Imbo\Config\ArrayConfig::getResources
     */
    public function testGetResources() {
        $resources = [];

        $config = new ArrayConfig([
            'resources' => $resources
        ]);

        $this->assertSame($resources, $config->getResources());
    }

    /**
     * @covers Imbo\Config\ArrayConfig::getRoutes
     */
    public function testGetRoutes() {
        $routes = [];

        $config = new ArrayConfig([
            'routes' => $routes
        ]);

        $this->assertSame($routes, $config->getRoutes());
    }

    /**
     * @covers Imbo\Config\ArrayConfig::getTrustedProxies
     */
    public function testGetTrustedProxies() {
        $trustedProxies = [];

        $config = new ArrayConfig([
            'trustedProxies' => $trustedProxies
        ]);

        $this->assertSame($trustedProxies, $config->getTrustedProxies());
    }

    /**
     * @covers Imbo\Config\ArrayConfig::getIndexRedirect
     */
    public function testGetIndexRedirect() {
        $indexRedirect = [];

        $config = new ArrayConfig([
            'indexRedirect' => $indexRedirect
        ]);

        $this->assertSame($indexRedirect, $config->getIndexRedirect());
    }
}
