<?php declare(strict_types=1);
namespace Imbo\Image;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use Symfony\Component\HttpFoundation\ParameterBag;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\TransformationManager
 */
class TransformationManagerTest extends TestCase {
    protected $manager;
    protected $request;
    protected $response;
    protected $event;
    protected $query;
    protected $image;
    private $config;

    public function setUp() : void {
        $this->config = require __DIR__ . '/../../../../config/config.default.php';
        $this->manager = new TransformationManager();
        $this->manager->addTransformations($this->config['transformations']);
        $this->query = new ParameterBag([]);
        $this->request = new Request();
        $this->request->query = $this->query;

        $this->image = $this->createConfiguredMock(Image::class, [
            'getWidth' => 1600,
            'getHeight' => 900,
        ]);

        $this->response = $this->createConfiguredMock(Response::class, [
            'getModel' => $this->image,
        ]);

        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
        ]);
    }

    /**
     * @covers ::getMinimumImageInputSize
     */
    public function testFindsTheMinimumImageInputSizeForSingleTransformation() : void {
        $this->query->set('t', ['maxSize:width=1024']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);

        $this->assertSame(1024, $minimum['width']);
        $this->assertSame(576, $minimum['height']);
    }

    /**
     * @covers ::getMinimumImageInputSize
     */
    public function testFindsTheMinimumImageInputSizeForMultipleTransformations() : void {
        $this->query->set('t', ['maxSize:width=1024', 'maxSize:height=620']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);

        $this->assertSame(1024, $minimum['width']);
        $this->assertSame(576, $minimum['height']);

        // Regardless of order
        $this->query->set('t', ['maxSize:height=620', 'maxSize:width=1024']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);

        $this->assertSame(1024, $minimum['width']);
        $this->assertSame(576, $minimum['height']);
    }

    /**
     * @covers ::getMinimumImageInputSize
     */
    public function testFindsTheMinimumImageInputSizeForRotatedImages() : void {
        $this->query->set('t', ['rotate:angle=90', 'maxSize:width=600']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);

        $this->assertSame(1067, $minimum['width']);
        $this->assertSame(600, $minimum['height']);
    }

    /**
     * @covers ::getMinimumImageInputSize
     */
    public function testFindsTheMinimumImageInputSizeForDoublyRotatedImages() : void {
        $this->query->set('t', [
            'rotate:angle=90',
            'maxSize:width=500',
            'rotate:angle=-90',
            'maxSize:width=320'
        ]);

        $minimum = $this->manager->getMinimumImageInputSize($this->event);

        $this->assertSame(320, $minimum['width']);
        $this->assertSame(180, $minimum['height']);
    }

    /**
     * @covers ::getMinimumImageInputSize
     */
    public function testReturnsFalseIfMinimumSizeIsLargerThanOriginal() : void {
        $this->query->set('t', ['resize:width=3800,height=1800']);
        $this->assertFalse($this->manager->getMinimumImageInputSize($this->event));
    }

    /**
     * @covers ::getMinimumImageInputSize
     */
    public function testSkipsTransformationsThatReturnNullAsMinInputSize() : void {
        $this->query->set('t', ['maxSize:width=10000']);
        $this->assertFalse($this->manager->getMinimumImageInputSize($this->event));
    }

    /**
     * @covers ::getMinimumImageInputSize
     */
    public function testReturnsCorrectSizeIfChainIsNotStopped() : void {
        // Sanity check for the test that follows
        $this->query->set('t', ['maxSize:width=750', 'maxSize:width=320']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);
        $this->assertSame(320, $minimum['width']);
    }

    /**
     * @covers ::getMinimumImageInputSize
     */
    public function testStopsMinSizeChainIfTransformationReturnsFalse() : void {
        $this->query->set('t', ['maxSize:width=750', 'rotate:angle=17.3', 'maxSize:width=320']);

        $minimum = $this->manager->getMinimumImageInputSize($this->event);
        $this->assertSame(750, $minimum['width']);
        $this->assertSame(422, $minimum['height']);
    }

    /**
     * @covers ::getMinimumImageInputSize
     */
    public function testFindsRightSizeWhenRegionIsExtracted() : void {
        $this->query->set('t', ['crop:width=784,height=700,x=384,y=200', 'maxSize:width=320']);

        $minimum = $this->manager->getMinimumImageInputSize($this->event);
        $this->assertSame(654, $minimum['width']);
        $this->assertSame(368, $minimum['height']);
    }
}
