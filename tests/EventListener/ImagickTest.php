<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imagick as I;
use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Image\InputLoaderManager;
use Imbo\Image\TransformationManager;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(Imagick::class)]
class ImagickTest extends ListenerTests
{
    private Imagick $listener;
    private Request&MockObject $request;
    private Response&MockObject $response;
    private EventInterface&MockObject $event;
    private TransformationManager&MockObject $transformationManager;
    private InputLoaderManager&MockObject $inputLoaderManager;

    public function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->transformationManager = $this->createMock(TransformationManager::class);
        $this->inputLoaderManager = $this->createMock(InputLoaderManager::class);
        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getTransformationManager' => $this->transformationManager,
            'getInputLoaderManager' => $this->inputLoaderManager,
        ]);

        $this->listener = new Imagick();
    }

    protected function getListener(): Imagick
    {
        return $this->listener;
    }

    public function testFetchesImageFromRequest(): void
    {
        $image = $this->createConfiguredMock(Image::class, [
            'getBlob' => 'image',
            'getMimeType' => 'image/jpeg',
        ]);

        $this->request
            ->expects($this->once())
            ->method('getImage')
            ->willReturn($image);

        $this->inputLoaderManager
            ->expects($this->once())
            ->method('load')
            ->with('image/jpeg', 'image');

        $this->event
            ->expects($this->once())
            ->method('getName')
            ->willReturn('images.post');

        $this->event
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn([
                'optimizations' => [
                    'jpegSizeHint' => false,
                ],
            ]);

        $this->listener->readImageBlob($this->event);
    }

    public function testFetchesImageFromResponse(): void
    {
        $image = $this->createConfiguredMock(Image::class, [
            'getBlob' => 'image',
            'getMimeType' => 'image/jpeg',
        ]);

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($image);

        $this->inputLoaderManager
            ->expects($this->once())
            ->method('load')
            ->with('image/jpeg', 'image');

        $this->event
            ->expects($this->once())
            ->method('getName')
            ->willReturn('storage.image.load');

        $this->event
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn([
                'optimizations' => [
                    'jpegSizeHint' => false,
                ],
            ]);

        $this->listener->readImageBlob($this->event);
    }

    public function testFetchesImageFromEvent(): void
    {
        $image = $this->createConfiguredMock(Image::class, [
            'getBlob' => 'image',
            'getMimeType' => 'image/png',
        ]);

        $this->event
            ->expects($this->once())
            ->method('hasArgument')
            ->with('image')
            ->willReturn(true);
        $this->event
            ->expects($this->once())
            ->method('getArgument')
            ->with('image')
            ->willReturn($image);

        $this->inputLoaderManager
            ->expects($this->once())
            ->method('load')
            ->with('image/png', 'image');

        $this->event
            ->expects($this->once())
            ->method('getName')
            ->willReturn('images.post');

        $this->event
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn([
                'optimizations' => [
                    'jpegSizeHint' => false,
                ],
            ]);

        $this->listener->readImageBlob($this->event);
    }

    #[DataProvider('hasImageBeenTransformed')]
    public function testUpdatesModelBeforeStoring(bool $hasBeenTransformed): void
    {
        /** @var Image&MockObject */
        $image = $this->createConfiguredMock(Image::class, [
            'getHasBeenTransformed' => $hasBeenTransformed,
        ]);

        /** @var I&MockObject */
        $imagick = $this->createMock(I::class);

        if ($hasBeenTransformed) {
            $imagick
                ->expects($this->once())
                ->method('getImageBlob')
                ->willReturn('image');
            $image
                ->expects($this->once())
                ->method('setBlob')
                ->with('image');
        } else {
            $imagick
                ->expects($this->never())
                ->method('getImageBlob');
            $image
                ->expects($this->never())
                ->method('setBlob');
        }

        $this->request
            ->expects($this->once())
            ->method('getImage')
            ->willReturn($image);

        $this->listener
            ->setImagick($imagick)
            ->updateModelBeforeStoring($this->event);
    }

    #[DataProvider('hasImageBeenTransformed')]
    public function testUpdatesModelBeforeSendingResponse(bool $hasBeenTransformed): void
    {
        /** @var Image&MockObject */
        $image = $this->createConfiguredMock(Image::class, [
            'getHasBeenTransformed' => $hasBeenTransformed,
        ]);

        /** @var I&MockObject */
        $imagick = $this->createMock(I::class);

        if ($hasBeenTransformed) {
            $imagick
                ->expects($this->once())
                ->method('getImageBlob')
                ->willReturn('image');
            $image
                ->expects($this->once())
                ->method('setBlob')
                ->with('image');
        } else {
            $imagick
                ->expects($this->never())
                ->method('getImageBlob');
            $image
                ->expects($this->never())
                ->method('setBlob');
        }

        $this->event
            ->expects($this->once())
            ->method('getArgument')
            ->with('image')
            ->willReturn($image);

        $this->listener
            ->setImagick($imagick)
            ->updateModel($this->event);
    }

    public function testCanOptimizeImage(): void
    {
        $this->event
            ->expects($this->once())
            ->method('getName')
            ->willReturn('image.loaded');
        $this->event
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn(['optimizations' => ['jpegSizeHint' => true]]);

        $event = $this->event;
        $this->event
            ->method('setArgument')
            ->willReturnCallback(
                static function (string $arg, int $value) use ($event): EventInterface&MockObject {
                    /** @var int */
                    static $i = 0;
                    return match ([$i++, $arg, $value]) {
                        [0, 'ratio', 4],
                        [1, 'transformationIndex', 0] => $event,
                    };
                },
            );

        $this->transformationManager
            ->expects($this->once())
            ->method('getMinimumImageInputSize')
            ->with($this->event)
            ->willReturn(['width' => 30, 'height' => 20, 'index' => 0]);

        /** @var I&MockObject */
        $imagick = $this->createConfiguredMock(I::class, [
            'getImageGeometry' => ['width' => 32, 'height' => 32],
        ]);
        $imagick
            ->expects($this->once())
            ->method('setOption')
            ->with('jpeg:size', '30x20');

        $image = $this->createConfiguredMock(Image::class, [
            'getWidth' => 128,
            'getMimeType' => 'image/jpeg',
            'getBlob' => 'blob',
        ]);

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($image);

        $this->listener
            ->setImagick($imagick)
            ->readImageBlob($this->event);
    }

    /**
     * @return array<array{hasBeenTransformed:bool}>
     */
    public static function hasImageBeenTransformed(): array
    {
        return [
            'has been transformed' => ['hasBeenTransformed' => true],
            'has not been transformed' => ['hasBeenTransformed' => false],
        ];
    }
}
