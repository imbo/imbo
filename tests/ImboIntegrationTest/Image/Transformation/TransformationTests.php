<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\Image\Transformation;

/**
 * @group integration
 * @group transformations
 */
abstract class TransformationTests extends \PHPUnit_Framework_TestCase {
    /**
     * Get the transformation to test
     *
     * @return Imbo\Image\Transformation\Transformation
     */
    abstract protected function getTransformation();

    /**
     * Make sure we have Imagick available
     */
    public function setUp() {
        if (!class_exists('Imagick')) {
            $this->markTestSkipped('Imagick must be available to run this test');
        }
    }

    public function testReturnsACorrectEventSubscriptionArray() {
        $transformation = $this->getTransformation();
        $this->assertInternalType('array', $transformation::getSubscribedEvents());
    }
}
