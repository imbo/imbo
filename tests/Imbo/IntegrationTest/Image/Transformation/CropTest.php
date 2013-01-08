<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\IntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Crop;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 * @covers Imbo\Image\Transformation\Crop
 */
class CropTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Crop(array(
            'width' => 1,
            'height' => 2,
            'x' => 3,
            'y' => 4,
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedName() {
        return 'crop';
    }

    /**
     * {@inheritdoc}
     * @covers Imbo\Image\Transformation\Crop::applyToImage
     */
    protected function getImageMock() {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('setWidth')->with(1)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with(2)->will($this->returnValue($image));

        return $image;
    }
}
