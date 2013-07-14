<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\IntegrationTest\Storage;

use Imbo\Storage\S3,
    Aws\S3\S3Client;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 */
class S3Test extends StorageTests {
    /**
     * @see Imbo\IntegrationTest\Storage\StorageTests::getDriver()
     */
    protected function getDriver() {
        return new S3(array(
            'key' => $GLOBALS['AWS_S3_KEY'],
            'secret' => $GLOBALS['AWS_S3_SECRET'],
            'bucket' => $GLOBALS['AWS_S3_BUCKET'],
        ));
    }

    /**
     * Make sure we have the correct config available
     */
    public function setUp() {
        foreach (array('AWS_S3_KEY', 'AWS_S3_SECRET', 'AWS_S3_BUCKET') as $key) {
            if (empty($GLOBALS[$key])) {
                $this->markTestSkipped('This test needs the ' . $key . ' value to be set in phpunit.xml');
            }
        }

        $client = S3Client::factory(array(
            'key' => $GLOBALS['AWS_S3_KEY'],
            'secret' => $GLOBALS['AWS_S3_SECRET'],
        ));
        $client->clearBucket($GLOBALS['AWS_S3_BUCKET']);

        parent::setUp();
    }
}
