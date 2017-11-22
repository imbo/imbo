<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Storage;

use Imbo\Exception\ConfigurationException;
use Imbo\Storage\S3;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Storage\S3
 * @group unit
 * @group storage
 */
class S3Test extends TestCase {
    /**
     * Test that we don't get an exception with required parameters present
     */
    public function testConstructorWithAllRequiredParameters() {
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
     *
     * @expectedException Imbo\Exception\ConfigurationException
     * @expectedExceptionMessageRegExp /: key, bucket/
     */
    public function testConstructorMissingRequiredParameters() {
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
    public function testConstructorEachMissingRequiredParameters() {
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
