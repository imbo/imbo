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

use Imbo\Image\Transformation\Desaturate;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 */
class DesaturateTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Desaturate();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedName() {
        return 'desaturate';
    }

    /**
     * {@inheritdoc}
     * @covers Imbo\Image\Transformation\Desaturate::applyToImage
     */
    protected function getImageMock() {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));

        return $image;
    }
}
