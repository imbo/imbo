<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Image\Transformation;

use Imbo\Image\Transformation\Strip,
    ImagickException;

/**
 * @covers Imbo\Image\Transformation\Strip
 * @group unit
 * @group transformations
 */
class StripTest extends \PHPUnit_Framework_TestCase {
    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionMessage error
     * @expectedExceptionCode 400
     */
    public function testThrowsCorrectExceptionWhenAnErrorOccurs() {
        $imagickException = new ImagickException('error');

        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->once())->method('stripImage')->will($this->throwException($imagickException));

        $transformation = new Strip();
        $transformation->setImagick($imagick)->transform([]);
    }

    public function testReloadsImageIfNewerImagick() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->once())->method('getImageBlob')->will($this->returnValue('foo'));
        $imagick->expects($this->once())->method('clear');
        $imagick->expects($this->once())->method('readImageBlob')->with('foo');

        $transformation = new Strip();
        $transformation->setImagick($imagick)->setImage($image)->transform([]);
    }
}
