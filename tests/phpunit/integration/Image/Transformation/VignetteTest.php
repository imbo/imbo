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

use Imbo\Image\Transformation\Vignette;
use Imagick;

/**
 * @covers Imbo\Image\Transformation\Vignette
 * @group integration
 * @group transformations
 */
class VignetteTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Vignette();
    }

    /**
     * @covers Imbo\Image\Transformation\Vignette::transform
     */
    public function testCanVignetteImages() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(640));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(480));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([]);
    }
}
