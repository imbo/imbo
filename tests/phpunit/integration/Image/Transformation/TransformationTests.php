<?php declare(strict_types=1);
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Transformation;
use PHPUnit\Framework\TestCase;

abstract class TransformationTests extends TestCase {
    /**
     * Get the transformation to test
     *
     * @return Transformation
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

    public function testReturnsACorrectEventSubscriptionArray() : void {
        $transformation = $this->getTransformation();
        $this->assertIsArray($transformation::getSubscribedEvents());
    }
}
