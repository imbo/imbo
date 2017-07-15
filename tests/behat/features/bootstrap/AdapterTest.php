<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboBehatFeatureContext;

/**
 * Interface for adapter test classes
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
interface AdapterTest {
    /**
     * Set up the environment for the adapter that is being tested
     *
     * This method will be called from the Behat suite, and the configuration array returned will
     * be sent to the Imbo installation under test as a request header, and the configuration will
     * be fed back into the getAdapter method.
     *
     * @param array $config Suite configuration from from behat.yml[.dist]
     * @return array
     */
    static public function setUp(array $config);

    /**
     * Tear down the environment for the adapter that is being tested
     *
     * This method will be called from the Behat suite, and the parameter is the configuration array
     * returned from the initial call to the setUp method.
     *
     * @param array $config Configuration returned from the setUp method
     */
    static public function tearDown(array $config);

    /**
     * Get an instance of the adapter under test
     *
     * This method will be called from the Imbo installation under test, and the configuration
     * parameter is the one originally returned from the setUp method, and sent to the Imbo
     * installation as a request header.
     *
     * @param array $config Configuration returned from the setUp method
     * @return Imbo\Database\DatabaseInterface|Imbo\Storage\StorageInterface
     */
    static public function getAdapter(array $config);
}
