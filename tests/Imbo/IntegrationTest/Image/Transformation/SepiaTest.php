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

use Imbo\Image\Transformation\Sepia;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Test suite\Integration tests
 * @covers Imbo\Image\Transformation\Sepia
 */
class SepiaTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Sepia();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultParams() {
        return array('threshold' => 90);
    }

    /**
     * {@inheritdoc}
     */
    protected function getImageMock() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));

        return $image;
    }
}
