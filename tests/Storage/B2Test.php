<?php declare(strict_types=1);
namespace Imbo\Storage;

use Imbo\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Storage\B2
 */
class B2Test extends TestCase {
    /**
     * @covers ::__construct
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
     * @covers ::__construct
     */
    public function testConstructorMissingRequiredParameters() : void {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/: accountId, bucketId/');
        $this->expectExceptionCode(500);

        $b2 = new B2([
            'accountId' => '',
            'applicationKey' => 'bar',
            'bucketId' => '',
            'bucket' => 'eggs'
        ]);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorEachMissingRequiredParameters() : void {
        $params = [
            'accountId' => 'foo',
            'applicationKey' => 'bar',
            'bucketId' => 'spam',
            'bucket' => 'eggs'
        ];

        foreach (array_keys($params) as $param) {
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