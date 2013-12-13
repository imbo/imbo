<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Image;

use Imbo\Image\ImagePreparation;

/**
 * @covers Imbo\Image\ImagePreparation
 * @group unit
 */
class ImagePreparationTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ImagePreparation
     */
    private $preparation;

    private $request;
    private $event;

    /**
     * Set up the image preparation instance
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $this->prepare = new ImagePreparation();
    }

    /**
     * Tear down the image prepration instance
     */
    public function tearDown() {
        $this->preparation = null;
        $this->request = null;
        $this->event = null;
    }

    /**
     * @covers Imbo\Image\ImagePreparation::getSubscribedEvents
     */
    public function testReturnsACorrectDefinition() {
        $class = get_class($this->prepare);
        $this->assertInternalType('array', $class::getSubscribedEvents());
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage No image attached
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenNoImageIsAttached() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(''));

        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage Hash mismatch
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionWhenImageInRequestDoesNotMatchImageIdentifierInUrl() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('asd'));

        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage Invalid image
     * @expectedExceptionCode 415
     */
    public function testThrowsExceptionWhenImageTypeIsNotSupported() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(file_get_contents(__FILE__)));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue(md5_file(__FILE__)));

        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage Invalid image
     * @expectedExceptionCode 415
     */
    public function testThrowsExceptionWhenImageIsBroken() {
        $filePath = FIXTURES_DIR . '/broken-image.jpg';

        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(file_get_contents($filePath)));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue(md5_file($filePath)));

        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage Invalid image
     * @expectedExceptionCode 415
     */
    public function testThrowsExceptionWhenImageIsBrokenButSizeIsReadable() {
        $filePath = FIXTURES_DIR . '/slightly-broken-image.png';

        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(file_get_contents($filePath)));
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

        $this->request->expects($this->once())->method('getContent')->will($this->returnValue($imageData));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $this->request->expects($this->once())->method('setImage')->with($this->isInstanceOf('Imbo\Model\Image'));

        $this->prepare->prepareImage($this->event);
    }
}
