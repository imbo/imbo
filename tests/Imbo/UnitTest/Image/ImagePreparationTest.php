<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Image;

use Imbo\Image\ImagePreparation;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class ImagePreparationTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ImagePreparation
     */
    private $preparation;

    private $request;
    private $event;
    private $container;

    /**
     * Set up the image preparation instance
     *
     * @covers Imbo\Image\ImagePreparation::setContainer
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->container = $this->getMock('Imbo\Container');
        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $this->prepare = new ImagePreparation();
        $this->prepare->setContainer($this->container);
    }

    /**
     * Tear down the image prepration instance
     */
    public function tearDown() {
        $this->preparation = null;
        $this->request = null;
        $this->container = null;
        $this->event = null;
    }

    /**
     * @covers Imbo\Image\ImagePreparation::getDefinition
     */
    public function testReturnsACorrectDefinition() {
        $definition = $this->prepare->getDefinition();
        $this->assertInternalType('array', $definition);

        foreach ($definition as $d) {
            $this->assertInstanceOf('Imbo\EventListener\ListenerDefinition', $d);
        }
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage No image attached
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenNoImageIsAttached() {
        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue(''));

        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage Hash mismatch
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenImageInRequestDoesNotMatchImageIdentifierInUrl() {
        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('asd'));

        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage Unsupported image type
     * @expectedExceptionCode 415
     */
    public function testThrowsExceptionWhenImageTypeIsNotSupported() {
        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue(file_get_contents(__FILE__)));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue(md5_file(__FILE__)));

        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage Broken image
     * @expectedExceptionCode 415
     */
    public function testThrowsExceptionWhenImageIsBroken() {
        $filePath = FIXTURES_DIR . '/broken-image.jpg';

        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue(file_get_contents($filePath)));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue(md5_file($filePath)));

        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     */
    public function testPopulatesRequestWhenImageIsValid() {
        $imagePath = FIXTURES_DIR . '/image.png';
        $imageData = file_get_contents($imagePath);
        $imageIdentifier = md5($imageData);

        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue($imageData));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));

        $image = $this->getMock('Imbo\Model\Image');
        $this->container->expects($this->once())->method('get')->with('image')->will($this->returnValue($image));

        $this->request->expects($this->once())->method('setImage')->with($image);

        $image->expects($this->once())->method('setMimeType')->with('image/png')->will($this->returnSelf());
        $image->expects($this->once())->method('setExtension')->with('png')->will($this->returnSelf());
        $image->expects($this->once())->method('setBlob')->with($imageData)->will($this->returnSelf());
        $image->expects($this->once())->method('setWidth')->with(665)->will($this->returnSelf());
        $image->expects($this->once())->method('setHeight')->with(463)->will($this->returnSelf());

        $this->prepare->prepareImage($this->event);
    }
}
