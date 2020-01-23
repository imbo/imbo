<?php declare(strict_types=1);
namespace Imbo\Storage;

use Imbo\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Storage\S3
 */
class S3Test extends TestCase {
    /**
     * @covers ::__construct
     */
    public function testConstructorWithAllRequiredParameters() : void {
        $this->assertNotNull(new S3([
            'key' => 'foo',
            'secret' => 'bar',
            'bucket' => 'spam',
            'region' => 'eggs',
        ]));
    }

    /**
     * @covers ::__construct
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
     * @covers ::__construct
     */
    public function testConstructorEachMissingRequiredParameters() : void {
        $params = [
            'key' => 'foo',
            'secret' => 'bar',
            'bucket' => 'spam',
            'region' => 'eggs',
        ];

        foreach (array_keys($params) as $param) {
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
