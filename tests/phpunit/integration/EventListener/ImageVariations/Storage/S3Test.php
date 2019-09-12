<?php
namespace ImboIntegrationTest\EventListener\ImageVariations\Storage;

use Imbo\EventListener\ImageVariations\Storage\S3;
use ImboIntegrationTest\Storage\S3Test as S3TestMain;
use Aws\S3\S3Client;

/**
 * @coversDefaultClass Imbo\EventListener\ImageVariations\Storage\S3
 * @group local
 */
class S3Test extends StorageTests {
    /**
     * @see ImboIntegrationTest\Storage\StorageTests::getDriver()
     */
    protected function getAdapter() {
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
    public function setUp() : void {
        foreach (['AWS_S3_KEY', 'AWS_S3_SECRET', 'AWS_S3_BUCKET'] as $key) {
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

        S3TestMain::clearBucket($client, $GLOBALS['AWS_S3_BUCKET']);

        parent::setUp();
    }
}
