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

use Imbo\Image\ImagePreparation,
    Imbo\Model\Image,
    Imbo\Image\Identifier\Generator\GeneratorInterface;

/**
 * @covers Imbo\Image\ImagePreparation
 * @group unit
 */
class ImagePreparationTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ImagePreparation
     */
    private $prepare;

    private $request;
    private $response;
    private $event;
    private $config;
    private $database;
    private $headers;
    private $imageIdentifierGenerator;

    /**
     * Set up the image preparation instance
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->headers = $this->getMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $this->response->headers = $this->headers;
        $this->imageIdentifierGenerator = $this->getMock('Imbo\Image\Identifier\Generator\GeneratorInterface');
        $this->config = ['imageIdentifierGenerator' => $this->imageIdentifierGenerator];
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getConfig')->will($this->returnValue($this->config));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));

        $this->prepare = new ImagePreparation();
    }

    /**
     * Tear down the image prepration instance
     */
    public function tearDown() {
        $this->imageIdentifierGenerator = null;
        $this->database = null;
        $this->response = null;
        $this->prepare = null;
        $this->headers = null;
        $this->request = null;
        $this->config = null;
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
     * @expectedExceptionMessage Unsupported image type: text/x-php
     * @expectedExceptionCode 415
     */
    public function testThrowsExceptionWhenImageTypeIsNotSupported() {
        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(file_get_contents(__FILE__)));
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
        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage Invalid image
     * @expectedExceptionCode 415
     */
    public function testThrowsExceptionWhenImageIsSlightlyBroken() {
        $filePath = FIXTURES_DIR . '/slightly-broken-image.png';

        $this->request->expects($this->once())->method('getContent')->will($this->returnValue(file_get_contents($filePath)));
        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @covers Imbo\Image\ImagePreparation::generateImageIdentifier
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage Failed to generate unique image identifier
     * @expectedExceptionCode 503
     */
    public function testThrowsExceptionWhenItFailsToGenerateUniqueImageIdentifier() {
        $imagePath = FIXTURES_DIR . '/image.png';
        $imageData = file_get_contents($imagePath);

        $this->request->expects($this->once())->method('getContent')->will($this->returnValue($imageData));
        $this->database->expects($this->any())->method('imageExists')->will($this->returnValue(true));
        $this->headers->expects($this->once())->method('set')->with('Retry-After', 1);
        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     */
    public function testPopulatesRequestWhenImageIsValid() {
        $imagePath = FIXTURES_DIR . '/image.png';
        $imageData = file_get_contents($imagePath);

        $this->request->expects($this->once())->method('getContent')->will($this->returnValue($imageData));
        $this->request->expects($this->once())->method('setImage')->with($this->isInstanceOf('Imbo\Model\Image'));
        $this->prepare->prepareImage($this->event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @covers Imbo\Image\ImagePreparation::generateImageIdentifier
     */
    public function testInstantiatesImageIdentifierGeneratorOnCallable() {
        $imagePath = FIXTURES_DIR . '/image.png';
        $imageData = file_get_contents($imagePath);

        $generator = $this->imageIdentifierGenerator;
        $config['imageIdentifierGenerator'] = function() use ($generator) {
            return $generator;
        };

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $event->expects($this->any())->method('getConfig')->will($this->returnValue($config));
        $event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));

        $this->request->expects($this->once())->method('getContent')->will($this->returnValue($imageData));
        $this->request->expects($this->once())->method('setImage')->with($this->isInstanceOf('Imbo\Model\Image'));
        $this->prepare->prepareImage($event);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @covers Imbo\Image\ImagePreparation::generateImageIdentifier
     */
    public function testDoesNotInstantiateCallableGenerator() {
        $imagePath = FIXTURES_DIR . '/image.png';
        $imageData = file_get_contents($imagePath);

        $config['imageIdentifierGenerator'] = new CallableImageIdentifierGenerator();

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $event->expects($this->any())->method('getConfig')->will($this->returnValue($config));
        $event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));

        $this->request->expects($this->once())->method('getContent')->will($this->returnValue($imageData));
        $this->request->expects($this->once())->method('setImage')->with($this->isInstanceOf('Imbo\Model\Image'));
        $this->prepare->prepareImage($event);
    }
}

class CallableImageIdentifierGenerator implements GeneratorInterface {
    public function generate(Image $image) {}
    public function isDeterministic() {}
    public function __invoke() {}
}
