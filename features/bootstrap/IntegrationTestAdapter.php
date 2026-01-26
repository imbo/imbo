<?php declare(strict_types=1);

namespace Imbo\Behat;

use Imbo\Database\DatabaseInterface;
use Imbo\Storage\StorageInterface;

/**
 * Interface for adapters used in the integration tests.
 */
interface IntegrationTestAdapter
{
    /**
     * Set up the environment for the adapter that is being tested.
     */
    public function setUp(): void;

    /**
     * Get an instance of the adapter under test.
     */
    public function getAdapter(): DatabaseInterface|StorageInterface;
}
