<?php declare(strict_types=1);
namespace Imbo\Storage;

use Aws\S3\S3Client;

/**
 * @coversDefaultClass Imbo\Storage\S3
 * @group local
 */
class S3IntegrationTest extends StorageTests {
    protected function getDriver() {
        return new S3([
            'key' => AWS_S3_KEY,
            'secret' => AWS_S3_SECRET,
            'bucket' => AWS_S3_BUCKET,
            'region' => AWS_S3_REGION,
            'version' => '2006-03-01',
        ]);
    }

    /**
     * Make sure we have the correct config available
     */
    public function setUp() : void {
        foreach (['AWS_S3_KEY', 'AWS_S3_SECRET', 'AWS_S3_BUCKET', 'AWS_S3_REGION'] as $var) {
            if (!defined($var)) {
                $this->markTestSkipped(sprintf('This test needs the %s constant to be set in the PHPUnit configuration', $var));
            }
        }

        $client = new S3Client([
            'credentials' => [
                'key' => AWS_S3_KEY,
                'secret' => AWS_S3_SECRET,
            ],
            'region' => AWS_S3_REGION,
            'version' => '2006-03-01',
        ]);
        self::clearBucket($client, AWS_S3_BUCKET);

        parent::setUp();
    }

    /**
     * @covers ::getStatus
     */
    public function testGetStatus() : void {
        $this->assertTrue($this->getDriver()->getStatus());

        $driver = new S3([
            'key' => AWS_S3_KEY,
            'secret' => AWS_S3_SECRET,
            'region' => AWS_S3_REGION,
            'bucket' => uniqid(),
        ]);

        $this->assertFalse($driver->getStatus());
    }

    static public function clearBucket(S3Client $client, $bucket) {
        // Do we need to implement listVersions as well? For testing, this is not usually required..
        $objects = $client->getIterator('ListObjects', ['Bucket' => $bucket]);
        $keysToDelete = [];

        foreach ($objects as $object) {
            $keysToDelete[] = [
                'Key' => $object['Key'],
            ];
        }

        if (!$keysToDelete) {
            return;
        }

        $action = $client->deleteObjects([
            'Bucket' => $bucket,
            'Delete' => [
                'Objects' => $keysToDelete,
            ],
        ]);

        if (!empty($action['Errors'])) {
            var_dump($action['Errors']);
            return;
        }
    }
}
