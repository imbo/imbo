<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imbo\Database\DatabaseInterface;
use Imbo\Storage\StorageInterface;

/**
 * Interface for adapter test classes
 */
interface AdapterTest
{
    /**
     * Set up the environment for the adapter that is being tested
     *
     * This method will be called from the Behat suite, and the configuration array returned will
     * be sent to the Imbo installation under test as a request header, and the configuration will
     * be fed back into the getAdapter method.
     *
     * @param array<string,string> $config Suite configuration from from behat.yml[.dist]
     * @return array<string,string>
     */
    public static function setUp(array $config): array;

    /**
     * Tear down the environment for the adapter that is being tested
     *
     * This method will be called from the Behat suite, and the parameter is the configuration array
     * returned from the initial call to the setUp method.
     *
     * @param array<string,string> $config Configuration returned from the setUp method
     */
    public static function tearDown(array $config): void;

    /**
     * Get an instance of the adapter under test
     *
     * This method will be called from the Imbo installation under test, and the configuration
     * parameter is the one originally returned from the setUp method, and sent to the Imbo
     * installation as a request header.
     *
     * @param array<string,string> $config Configuration returned from the setUp method
     * @return DatabaseInterface|StorageInterface
     */
    public static function getAdapter(array $config);
}
