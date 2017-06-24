<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboBehatFeatureContext\Storage;

use Imbo\Storage\GridFS as Storage;
use MongoDB\Client as MongoClient;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

/**
 * Trait for suites that wants to use the GridFS storage adapter
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
trait GridFS {
    /**
     * The database name to use
     *
     * @var string
     */
    static private $storageDatabaseName = 'imbo_behat_test_storage';

    /**
     * Drop the test database before every scenario
     *
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    static public function dropTestStorageDatabase(BeforeScenarioScope $scope) {
        (new MongoClient())->{self::$storageDatabaseName}->drop();
    }

    /**
     * Return a configured GridFS storage adapter
     *
     * @return Storage
     */
    static public function getStorageAdapter() {
        return new Storage([
            'databaseName' => self::$storageDatabaseName,
        ]);
    }
}
