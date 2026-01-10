<?php declare(strict_types=1);

namespace Imbo\Image;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;

#[CoversClass(TransformationManager::class)]
class TransformationManagerTest extends TestCase
{
    protected TransformationManager $manager;
    protected Request $request;
    protected Response $response;
    protected EventInterface $event;
    protected InputBag $query;
    protected Image $image;
    private array $config;

    protected function setUp(): void
    {
        /** @var array{transformations:array<string,class-string>} $config */
        $config = require __DIR__.'/../../config/config.default.php';
        $this->config = $config;
        $this->manager = new TransformationManager();
        $this->manager->addTransformations($this->config['transformations']);
        $this->query = new InputBag([]);
        $this->request = new Request();
        $this->request->query = $this->query;

        $this->image = $this->createConfiguredStub(Image::class, [
            'getWidth' => 1600,
            'getHeight' => 900,
        ]);

        $this->response = $this->createConfiguredStub(Response::class, [
            'getModel' => $this->image,
        ]);

        $this->event = $this->createConfiguredStub(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
        ]);
    }

    public function testFindsTheMinimumImageInputSizeForSingleTransformation(): void
    {
        $this->query->set('t', ['maxSize:width=1024']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);
        $this->assertIsArray($minimum);
        $this->assertSame(1024, $minimum['width']);
        $this->assertSame(576, $minimum['height']);
    }

    public function testFindsTheMinimumImageInputSizeForMultipleTransformations(): void
    {
        $this->query->set('t', ['maxSize:width=1024', 'maxSize:height=620']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);
        $this->assertIsArray($minimum);
        $this->assertSame(1024, $minimum['width']);
        $this->assertSame(576, $minimum['height']);

        // Regardless of order
        $this->query->set('t', ['maxSize:height=620', 'maxSize:width=1024']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);
        $this->assertIsArray($minimum);
        $this->assertSame(1024, $minimum['width']);
        $this->assertSame(576, $minimum['height']);
    }

    public function testFindsTheMinimumImageInputSizeForRotatedImages(): void
    {
        $this->query->set('t', ['rotate:angle=90', 'maxSize:width=600']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);
        $this->assertIsArray($minimum);
        $this->assertSame(1067, $minimum['width']);
        $this->assertSame(600, $minimum['height']);
    }

    public function testFindsTheMinimumImageInputSizeForDoublyRotatedImages(): void
    {
        $this->query->set('t', [
            'rotate:angle=90',
            'maxSize:width=500',
            'rotate:angle=-90',
            'maxSize:width=320',
        ]);

        $minimum = $this->manager->getMinimumImageInputSize($this->event);
        $this->assertIsArray($minimum);
        $this->assertSame(320, $minimum['width']);
        $this->assertSame(180, $minimum['height']);
    }

    public function testReturnsFalseIfMinimumSizeIsLargerThanOriginal(): void
    {
        $this->query->set('t', ['resize:width=3800,height=1800']);
        $this->assertFalse($this->manager->getMinimumImageInputSize($this->event));
    }

    public function testSkipsTransformationsThatReturnNullAsMinInputSize(): void
    {
        $this->query->set('t', ['maxSize:width=10000']);
        $this->assertFalse($this->manager->getMinimumImageInputSize($this->event));
    }

    public function testReturnsCorrectSizeIfChainIsNotStopped(): void
    {
        // Sanity check for the test that follows
        $this->query->set('t', ['maxSize:width=750', 'maxSize:width=320']);
        $minimum = $this->manager->getMinimumImageInputSize($this->event);
        $this->assertIsArray($minimum);
        $this->assertSame(320, $minimum['width']);
    }

    public function testStopsMinSizeChainIfTransformationReturnsFalse(): void
    {
        $this->query->set('t', ['maxSize:width=750', 'rotate:angle=17.3', 'maxSize:width=320']);

        $minimum = $this->manager->getMinimumImageInputSize($this->event);
        $this->assertIsArray($minimum);
        $this->assertSame(750, $minimum['width']);
        $this->assertSame(422, $minimum['height']);
    }

    public function testFindsRightSizeWhenRegionIsExtracted(): void
    {
        $this->query->set('t', ['crop:width=784,height=700,x=384,y=200', 'maxSize:width=320']);

        $minimum = $this->manager->getMinimumImageInputSize($this->event);
        $this->assertIsArray($minimum);
        $this->assertSame(654, $minimum['width']);
        $this->assertSame(368, $minimum['height']);
    }
}
