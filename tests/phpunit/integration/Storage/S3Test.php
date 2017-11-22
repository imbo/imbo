<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\Storage;

use Imbo\Storage\S3;
use Aws\S3\S3Client;

/**
 * @covers Imbo\Storage\S3
 * @group integration
 * @group storage
 * @group aws
 */
class S3Test extends StorageTests {
    /**
     * @see ImboIntegrationTest\Storage\StorageTests::getDriver()
     */
    protected function getDriver() {
        return new S3([
            'key' => $GLOBALS['AWS_S3_KEY'],
            'secret' => $GLOBALS['AWS_S3_SECRET'],
            'bucket' => $GLOBALS['AWS_S3_BUCKET'],
            'region' => $GLOBALS['AWS_S3_REGION'],
            'version' => '2006-03-01',
        ]);
    }

    /**
     * Make sure we have the correct config available
     */
    public function setUp() {
        foreach (['AWS_S3_KEY', 'AWS_S3_SECRET', 'AWS_S3_BUCKET', 'AWS_S3_REGION'] as $key) {
            if (empty($GLOBALS[$key])) {
                $this->markTestSkipped('This test needs the ' . $key . ' value to be set in phpunit.xml');
            }
        }

        $client = new S3Client([
            'credentials' => [
                'key' => $GLOBALS['AWS_S3_KEY'],
                'secret' => $GLOBALS['AWS_S3_SECRET'],
            ],
            'region' => $GLOBALS['AWS_S3_REGION'],
            'version' => '2006-03-01',
        ]);
        self::clearBucket($client, $GLOBALS['AWS_S3_BUCKET']);

        parent::setUp();
    }

    /**
     * @covers Imbo\Storage\S3::getStatus
     */
    public function testGetStatus() {
        $this->assertTrue($this->getDriver()->getStatus());

        $driver = new S3([
            'key' => $GLOBALS['AWS_S3_KEY'],
            'secret' => $GLOBALS['AWS_S3_SECRET'],
            'region' => $GLOBALS['AWS_S3_REGION'],
            'bucket' => uniqid(),
        ]);

        $this->assertFalse($driver->getStatus());
    }

    static public function clearBucket(S3Client $client, $bucket) {
        // Do we need to implement listVersions as well? For testing, this is not usually required..
        $objects = $client->getIterator('ListObjects', array('Bucket' => $bucket));
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
