<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Storage;

use Imbo\Storage\S3IntegrationTest as S3TestMain;
use Aws\S3\S3Client;

/**
 * @coversDefaultClass Imbo\EventListener\ImageVariations\Storage\S3
 * @group local
 */
class S3Test extends StorageTests {
    protected function getAdapter() {
        return new S3([
            'key'     => AWS_S3_KEY,
            'secret'  => AWS_S3_SECRET,
            'bucket'  => AWS_S3_BUCKET,
            'region'  => AWS_S3_REGION,
            'version' => '2006-03-01',
        ]);
    }

    public function setUp() : void {
        foreach (['AWS_S3_KEY', 'AWS_S3_SECRET', 'AWS_S3_BUCKET'] as $var) {
            if (!defined($var)) {
                $this->markTestSkipped(sprintf('This test needs the %s value to be set in the PHPUnit configuration', $var));
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

        S3TestMain::clearBucket($client, AWS_S3_BUCKET);

        parent::setUp();
    }
}
