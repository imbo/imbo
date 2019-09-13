<?php
namespace ImboUnitTest\Storage;

use Imbo\Exception\ConfigurationException;
use Imbo\Storage\B2;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Storage\B2
 */
class B2Test extends TestCase {
    /**
     * Test that we don't get an exception with required parameters present
     */
    public function testConstructorWithAllRequiredParameters() : void {
        $b2 = new B2([
            'accountId' => 'foo',
            'applicationKey' => 'bar',
            'bucketId' => 'spam',
            'bucket' => 'eggs'
        ]);

        $this->assertNotNull($b2);
    }

    /**
     * Test that we _do_ get an exception with required parameters present
     */
    public function testConstructorMissingRequiredParameters() : void {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageRegExp('/: accountId, bucketId/');
        $this->expectExceptionCode(500);

        $b2 = new B2([
            'accountId' => '',
            'applicationKey' => 'bar',
            'bucketId' => '',
            'bucket' => 'eggs'
        ]);
    }

    /**
     * Test that we _do_ get exceptions for each single missing parameter
     */
    public function testConstructorEachMissingRequiredParameters() : void {
        $params = [
            'accountId' => 'foo',
            'applicationKey' => 'bar',
            'bucketId' => 'spam',
            'bucket' => 'eggs'
        ];

        foreach ($params as $param => $dummy) {
            $local = $params;
            unset($local[$param]);
            $exception = false;

            try {
                new B2($local);
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
