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

use Imbo\Image\Transformation\Rotate;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 */
class RotateTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Rotate(array('angle' => 45, 'bg' => 'bd1349'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedName() {
        return 'rotate';
    }

    /**
     * {@inheritdoc}
     * @covers Imbo\Image\Transformation\Rotate::applyToImage
     */
    protected function getImageMock() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('setWidth')->with($this->isType('int'))->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with($this->isType('int'))->will($this->returnValue($image));

        return $image;
    }
}
