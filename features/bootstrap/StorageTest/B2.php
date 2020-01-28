<?php declare(strict_types=1);
namespace Imbo\Behat\StorageTest;

use Imbo\Behat\AdapterTest;
use Imbo\Storage\B2 as Storage;
use ChrisWhite\B2\Client;
use ChrisWhite\B2\Bucket;

/**
 * Class for suites that want to use the B2 storage adapter
 */
class B2 implements AdapterTest {
    static private $client;

    /**
     * {@inheritdoc}
     */
    static public function setUp(array $config) {
        self::$client = new Client($config['storage.accountId'], $config['storage.applicationKey']);

        $bucket = self::$client->createBucket([
            'BucketName' => sprintf('imbo-integration-test-%s', uniqid()),
            'BucketType' => Bucket::TYPE_PRIVATE
        ]);

        return $config + [
            'storage.bucketId' => $bucket->getId(),
            'storage.bucketName' => $bucket->getName(),
        ];
    }

    static public function tearDown(array $config) {
        //$client = new Client($config['storage.accountId'], $config['storage.applicationKey']);
        self::$client->deleteBucket(['BucketId' => $config['storage.bucketId']]);
    }

    static public function getAdapter(array $config) : Storage {
        return new Storage([
            'accountId'      => $config['storage.accountId'],
            'applicationKey' => $config['storage.applicationKey'],
            'bucketId'       => $config['storage.bucketId'],
            'bucket'         => $config['storage.bucketName'],
        ]);
    }
}
