<?php
namespace ImboIntegrationTest\Image\Transformation;

use PHPUnit\Framework\TestCase;

/**
 * @group integration
 * @group transformations
 */
abstract class TransformationTests extends TestCase {
    /**
     * Get the transformation to test
     *
     * @return Imbo\Image\Transformation\Transformation
     */
    abstract protected function getTransformation();

    /**
     * Make sure we have Imagick available
     */
    public function setUp() : void {
        if (!class_exists('Imagick')) {
            $this->markTestSkipped('Imagick must be available to run this test');
        }
    }

    public function testReturnsACorrectEventSubscriptionArray() {
        $transformation = $this->getTransformation();
        $this->assertIsArray($transformation::getSubscribedEvents());
    }
}
