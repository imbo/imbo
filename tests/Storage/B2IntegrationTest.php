<?php declare(strict_types=1);
namespace Imbo\Storage;

use ChrisWhite\B2\Client;

/**
 * @coversDefaultClass Imbo\Storage\B2
 * @group local
 */
class B2IntegrationTest extends StorageTests {
    protected function getDriver() {
        return new B2([
            'accountId'      => BACKBLAZE_B2_ACCOUNT_ID,
            'applicationKey' => BACKBLAZE_B2_APPLICATION_KEY,
            'bucket'         => BACKBLAZE_B2_BUCKET,
            'bucketId'       => BACKBLAZE_B2_BUCKET_ID,
        ]);
    }

    public function setUp() : void {
        $required = ['BACKBLAZE_B2_ACCOUNT_ID', 'BACKBLAZE_B2_APPLICATION_KEY', 'BACKBLAZE_B2_BUCKET', 'BACKBLAZE_B2_BUCKET_ID'];
        $missing = [];

        foreach ($required as $var) {
            if (!defined($var)) {
                $missing[] = $var;
            }
        }

        if ($missing) {
            $this->markTestSkipped(sprintf('This test needs %s set in the PHPUnit configuration', join(', ', $missing)));
        }

        // delete any existing copies of our test file
        $b2client = new Client(BACKBLAZE_B2_ACCOUNT_ID, BACKBLAZE_B2_APPLICATION_KEY);
        $fname = sprintf('%s/%s', $this->user, $this->imageIdentifier);
        $exists = true;

        // B2 keeps old versions around, so we need to remove .. every single one.
        while ($exists) {
            $exists = $b2client->fileExists([
                'BucketId' => BACKBLAZE_B2_BUCKET_ID,
                'FileName' => $fname,
            ]);

            if ($exists) {
                $b2client->deleteFile([
                    'BucketId'   => BACKBLAZE_B2_BUCKET_ID,
                    'BucketName' => BACKBLAZE_B2_BUCKET,
                    'FileName'   => $fname,
                ]);
            }
        }

        parent::setUp();
    }
}
