<?php
namespace ImboUnitTest\Storage;

use Imbo\Exception\ConfigurationException;
use Imbo\Storage\S3;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Storage\S3
 */
class S3Test extends TestCase {
    /**
     * Test that we don't get an exception with required parameters present
     */
    public function testConstructorWithAllRequiredParameters() : void {
        $s3 = new S3([
            'key' => 'foo',
            'secret' => 'bar',
            'bucket' => 'spam',
            'region' => 'eggs',
        ]);

        $this->assertNotNull($s3);
    }

    /**
     * Test that we _do_ get an exception with required parameters present
     */
    public function testConstructorMissingRequiredParameters() : void {
        $this->expectExceptionObject(new ConfigurationException(
            'Missing required configuration parameters in Imbo\Storage\S3: key, bucket',
            500
        ));

        new S3([
            'key' => '',
            'secret' => 'bar',
            'bucket' => '',
            'region' => 'eggs',
        ]);
    }

    /**
     * Test that we _do_ get exceptions for each single missing parameter
     */
    public function testConstructorEachMissingRequiredParameters() : void {
        $params = [
            'key' => 'foo',
            'secret' => 'bar',
            'bucket' => 'spam',
            'region' => 'eggs',
        ];

        foreach ($params as $param => $dummy) {
            $local = $params;
            unset($local[$param]);
            $exception = false;

            try {
                new S3($local);
            } catch (ConfigurationException $e) {
                $exception = true;

                // test that the exception message ends with the field missing
                $this->assertStringEndsWith(': ' . $param, $e->getMessage());
            } finally {
                $this->assertTrue($exception);
            }
        }
    }
}
