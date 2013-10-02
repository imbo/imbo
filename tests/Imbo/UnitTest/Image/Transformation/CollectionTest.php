<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Image\Transformation;

use Imbo\Image\Transformation\Collection;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class CollectionTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\Image\Transformation\Collection::applyToImage
     */
    public function testApplyToImage() {
        $image = $this->getMock('Imbo\Model\Image');

        $border = $this->getMock('Imbo\Image\Transformation\TransformationInterface');
        $border->expects($this->once())->method('applyToImage')->with($image);

        $crop = $this->getMock('Imbo\Image\Transformation\TransformationInterface');
        $crop->expects($this->once())->method('applyToImage')->with($image);

        $transformation = new Collection(array(
            $border, $crop
        ));
        $transformation->applyToImage($image);
    }

    /**
     * @covers Imbo\Image\Transformation\Collection::setImageReader
     * @covers Imbo\Image\Transformation\Collection::getImageReader
     */
    public function testSetsTheImageReaderToAllTransformations() {
        $imageReader = $this->getMockBuilder('Imbo\Storage\ImageReader')->disableOriginalConstructor()->getMock();
        $transformation1 = $this->getMock('Imbo\Storage\ImageReaderAware');
        $transformation1->expects($this->once())->method('setImageReader')->with($imageReader);
        $transformation2 = $this->getMock('Imbo\Image\Transformation\TransformationInterface');

        $collection = new Collection(array(
            $transformation1, $transformation2
        ));
        $collection->setImageReader($imageReader);
        $this->assertSame($imageReader, $collection->getImageReader());
    }
}
