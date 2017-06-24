<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboBehatFeatureContext\Database;

use Imbo\Database\MongoDB as Database;
use MongoDB\Client as MongoClient;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

/**
 * Trait for suites that wants to use the MongoDB database adapter
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
trait MongoDB {
    /**
     * The database name to use
     *
     * @var string
     */
    static private $databaseName = 'imbo_behat_test_database';

    /**
     * Drop the test database before every scenario
     *
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    static public function dropTestDatabase(BeforeScenarioScope $scope) {
        (new MongoClient())->{self::$databaseName}->drop();
    }

    /**
     * Return a configured MongoDB database adapter
     *
     * @return Database
     */
    static public function getDatabaseAdapter() {
        return new Database([
            'databaseName' => self::$databaseName,
        ]);
    }
}
