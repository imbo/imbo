<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Imbo\Database\MongoDB;
use Imbo\Storage\GridFS;
use MongoDB\Client as MongoClient;

/**
 * Imbo Context
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Functional tests
 */
class MongoDB_GridFS extends FeatureContext implements ImboFeatureContext {
    /**
     * The database name to use
     *
     * @var string
     */
    static private $databaseName = 'imbo_testing';

    /**
     * Drop mongo test collection which stores information regarding images, and the images
     * themselves
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
     * @return MongoDB
     */
    static public function getDatabaseAdapter() {
        return new MongoDB([
            'databaseName' => self::$databaseName,
        ]);
    }

    /**
     * Return a configured GridFS storage adapter
     *
     * @return GridFS
     */
    static public function getStorageAdapter() {
        return new GridFS([
            'databaseName' => self::$databaseName,
        ]);
    }
}
