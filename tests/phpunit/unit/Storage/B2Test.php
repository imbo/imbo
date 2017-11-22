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
use Imbo\Storage\B2;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Storage\B2
 * @group unit
 * @group storage
 */
class B2Test extends TestCase {
    /**
     * Test that we don't get an exception with required parameters present
     */
    public function testConstructorWithAllRequiredParameters() {
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
     *
     * @expectedException Imbo\Exception\ConfigurationException
     * @expectedExceptionMessageRegExp /: accountId, bucketId/
     */
    public function testConstructorMissingRequiredParameters() {
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
    public function testConstructorEachMissingRequiredParameters() {
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
