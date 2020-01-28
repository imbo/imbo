<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use PHPUnit\Framework\TestCase;

abstract class TransformationTests extends TestCase {
    abstract protected function getTransformation();

    public function testReturnsACorrectEventSubscriptionArray() : void {
        $this->assertIsArray($this->getTransformation()::getSubscribedEvents());
    }
}
