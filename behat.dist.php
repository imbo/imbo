<?php declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Imbo\Behat\DatabaseTest\MongoDB;
use Imbo\Behat\DatabaseTest\MySQL;
use Imbo\Behat\DatabaseTest\PostgreSQL;
use Imbo\Behat\DatabaseTest\SQLite;
use Imbo\Behat\FeatureContext;
use Imbo\Behat\StorageTest\Filesystem;
use Imbo\Behat\StorageTest\GridFS;
use Imbo\BehatApiExtension\ServiceContainer\BehatApiExtension;

$imboBaseUri = 'http://localhost:8080';

$mongodbDatabaseName = 'imbo_behat_test_database';
$mongodbUri = 'mongodb://localhost:27017';
$mongodbUsername = 'admin';
$mongodbPassword = 'password';

$sqliteDsn = 'sqlite:/tmp/imbo-sqlite-integration-test.sq3';

$mysqlDsn = 'mysql:host=127.0.0.1;port=3333;dbname=imbo_test';
$mysqlUsername = 'imbo_test';
$mysqlPassword = 'imbo_test';

$postgresqlDsn = 'pgsql:host=127.0.0.1;port=5555;dbname=imbo_test';
$postgresqlUsername = 'imbo_test';
$postgresqlPassword = 'imbo_test';

$config = new Config();
$apiExtension = new Extension(BehatApiExtension::class, [
    'apiClient' => ['base_uri' => $imboBaseUri],
]);
$profile = new Profile('default');
$suiteSettings = static fn (array $c): array => [
    'project_root' => '%paths.base%',
    'contexts' => [FeatureContext::class],
] + $c;

return $config->withProfile(
    $profile
        ->withExtension($apiExtension)
        ->withSuite(new Suite('mongodb-gridfs', $suiteSettings([
            'database' => new MongoDB($mongodbDatabaseName, $mongodbUri, $mongodbUsername, $mongodbPassword),
            'storage' => new GridFS($mongodbDatabaseName, $mongodbUri, $mongodbUsername, $mongodbPassword),
        ])))
        ->withSuite(new Suite('mongodb-filesystem', $suiteSettings([
            'database' => new MongoDB($mongodbDatabaseName, $mongodbUri, $mongodbUsername, $mongodbPassword),
            'storage' => new Filesystem(),
        ])))
        ->withSuite(new Suite('sqlite-filesystem', $suiteSettings([
            'database' => new SQLite($sqliteDsn),
            'storage' => new Filesystem(),
        ])))
        ->withSuite(new Suite('mysql-filesystem', $suiteSettings([
            'database' => new MySQL($mysqlDsn, $mysqlUsername, $mysqlPassword),
            'storage' => new Filesystem(),
        ])))
        ->withSuite(new Suite('postgresql-filesystem', $suiteSettings([
            'database' => new PostgreSQL($postgresqlDsn, $postgresqlUsername, $postgresqlPassword),
            'storage' => new Filesystem(),
        ]))),
);
