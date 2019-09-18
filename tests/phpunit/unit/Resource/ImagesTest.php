<?php declare(strict_types=1);
namespace ImboUnitTest\Resource;

use Imbo\Resource\Images;
use Imbo\Exception\DuplicateImageIdentifierException;
use Imbo\Exception\ImageException;

/**
 * @coversDefaultClass Imbo\Resource\Images
 */
class ImagesTest extends ResourceTests {
    /**
     * @var Images
     */
    private $resource;

    private $request;
    private $response;
    private $database;
    private $storage;
    private $manager;
    private $event;
    private $imageIdentifierGenerator;
    private $config;

    protected function getNewResource() : Images {
        return new Images();
    }

    public function setUp() : void {
        $this->request = $this->createMock('Imbo\Http\Request\Request');
        $this->response = $this->createMock('Imbo\Http\Response\Response');
        $this->database = $this->createMock('Imbo\Database\DatabaseInterface');
        $this->storage = $this->createMock('Imbo\Storage\StorageInterface');
        $this->event = $this->createMock('Imbo\EventManager\Event');
        $this->manager = $this->createMock('Imbo\EventManager\EventManager');
        $this->imageIdentifierGenerator = $this->createMock('Imbo\Image\Identifier\Generator\GeneratorInterface');
        $this->config = ['imageIdentifierGenerator' => $this->imageIdentifierGenerator];

        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getDatabase')->will($this->returnValue($this->database));
        $this->event->expects($this->any())->method('getStorage')->will($this->returnValue($this->storage));
        $this->event->expects($this->any())->method('getManager')->will($this->returnValue($this->manager));
        $this->event->expects($this->any())->method('getConfig')->will($this->returnValue($this->config));

        $this->resource = $this->getNewResource();
    }

    /**
     * @covers Imbo\Resource\Images::addImage
     */
    public function testSupportsHttpPost() : void {
        $this->imageIdentifierGenerator->expects($this->any())->method('isDeterministic')->will($this->returnValue(false));
        $this->manager->expects($this->at(0))->method('trigger')->with('db.image.insert', ['updateIfDuplicate' => false]);
        $this->manager->expects($this->at(1))->method('trigger')->with('storage.image.insert');

        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));

        $this->request->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $this->response->expects($this->once())->method('setModel')->with($this->isInstanceOf('Imbo\Model\ArrayModel'));

        $this->resource->addImage($this->event);
    }

    /**
     * @covers Imbo\Resource\Images::addImage
     */
    public function testThrowsExceptionWhenItFailsToGenerateUniqueImageIdentifier() : void {
        $this->manager->expects($this->any())->method('trigger')->with('db.image.insert', ['updateIfDuplicate' => false])->will($this->throwException(new DuplicateImageIdentifierException()));

        $headers = $this->createMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $headers->expects($this->once())->method('set')->with('Retry-After', 1);
        $this->response->headers = $headers;

        $image = $this->createMock('Imbo\Model\Image');

        $this->imageIdentifierGenerator->expects($this->any())->method('isDeterministic')->will($this->returnValue(false));
        $this->imageIdentifierGenerator->expects($this->any())->method('generate')->will($this->returnValue('foo'));

        $this->request->expects($this->once())->method('getImage')->will($this->returnValue($image));

        $this->expectExceptionObject(new ImageException('Failed to generate unique image identifier', 503));
        $this->resource->addImage($this->event);
    }

    /**
     * @covers Imbo\Resource\Images::getImages
     */
    public function testSupportsHttpGet() : void {
        $this->manager->expects($this->once())->method('trigger')->with('db.images.load');
        $this->resource->getImages($this->event);
    }
}
