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

use Imbo\Storage\B2;
use ChrisWhite\B2\Client;
use ChrisWhite\B2\Exceptions\NotFoundException;

/**
 * @covers Imbo\Storage\B2
 * @group integration
 * @group storage
 * @group backblaze
 */
class B2Test extends StorageTests {
    /**
     * @see ImboIntegrationTest\Storage\StorageTests::getDriver()
     */
    protected function getDriver() {
        return new B2([
            'accountId' => $GLOBALS['BACKBLAZE_B2_ACCOUNT_ID'],
            'applicationKey' => $GLOBALS['BACKBLAZE_B2_APPLICATION_KEY'],
            'bucket' => $GLOBALS['BACKBLAZE_B2_BUCKET'],
            'bucketId' => $GLOBALS['BACKBLAZE_B2_BUCKET_ID'],
        ]);
    }

    /**
     * Make sure we have the correct config available
     */
    public function setUp() {
        $required = ['BACKBLAZE_B2_ACCOUNT_ID', 'BACKBLAZE_B2_APPLICATION_KEY', 'BACKBLAZE_B2_BUCKET', 'BACKBLAZE_B2_BUCKET_ID'];
        $missing = [];

        foreach ($required as $key) {
            if (empty($GLOBALS[$key])) {
                $missing[] = $key;
            }
        }

        if ($missing) {
            $this->markTestSkipped('This test needs ' . join(', ', $missing) . ' set in phpunit.xml');
        }

        // delete any existing copies of our test file
        $b2client = new Client($GLOBALS['BACKBLAZE_B2_ACCOUNT_ID'], $GLOBALS['BACKBLAZE_B2_APPLICATION_KEY']);
        $fname = $this->getUser() . '/' . $this->getImageIdentifier();
        $exists = true;

        // B2 keeps old versions around, so we need to remove .. every single one.
        while ($exists) {
            $exists = $b2client->fileExists([
                'BucketId' => $GLOBALS['BACKBLAZE_B2_BUCKET_ID'],
                'FileName' => $fname,
            ]);

            if ($exists) {
                $b2client->deleteFile([
                    'BucketId' => $GLOBALS['BACKBLAZE_B2_BUCKET_ID'],
                    'BucketName' => $GLOBALS['BACKBLAZE_B2_BUCKET'],
                    'FileName' => $fname,
                ]);
            }
        }

        parent::setUp();
    }
}
