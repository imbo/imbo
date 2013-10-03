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

use Imbo\Image\Transformation\Convert;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 * @covers Imbo\Image\Transformation\Convert
 */
class ConvertTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Convert();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultParams() {
        return array('type' => 'png');
    }

    /**
     * {@inheritdoc}
     */
    protected function getImageMock() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('setMimeType')->with('image/png');
        $image->expects($this->once())->method('setExtension')->with('png');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));

        return $image;
    }
}
