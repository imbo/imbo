<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\EventListener\ImageVariations\Storage;

use Imbo\EventListener\ImageVariations\Storage\S3,
    Aws\S3\S3Client;

/**
 * @covers Imbo\EventListener\ImageVariations\Storage\S3
 * @group integration
 * @group storage
 * @group aws
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
        ]);
    }

    /**
     * Make sure we have the correct config available
     */
    public function setUp() {
        foreach (['AWS_S3_KEY', 'AWS_S3_SECRET', 'AWS_S3_BUCKET'] as $key) {
            if (empty($GLOBALS[$key])) {
                $this->markTestSkipped('This test needs the ' . $key . ' value to be set in phpunit.xml');
            }
        }

        $client = S3Client::factory([
            'key' => $GLOBALS['AWS_S3_KEY'],
            'secret' => $GLOBALS['AWS_S3_SECRET'],
        ]);
        $client->clearBucket($GLOBALS['AWS_S3_BUCKET']);

        parent::setUp();
    }
}
