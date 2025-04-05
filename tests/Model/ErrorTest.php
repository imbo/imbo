<?php declare(strict_types=1);
namespace Imbo\Model;

use DateTime;
use Imbo\Exception;
use Imbo\Exception\RuntimeException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(Error::class)]
class ErrorTest extends TestCase
{
    private Error $model;

    public function setUp(): void
    {
        $this->model = new Error();
    }

    public function testCanSetAndGetHttpCode(): void
    {
        $this->assertNull($this->model->getHttpCode());
        $this->assertSame($this->model, $this->model->setHttpCode(Response::HTTP_NOT_FOUND));
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->model->getHttpCode());
    }

    public function testCanSetAndGetErrorMessage(): void
    {
        $this->assertNull($this->model->getErrorMessage());
        $this->assertSame($this->model, $this->model->setErrorMessage('message'));
        $this->assertSame('message', $this->model->getErrorMessage());
    }

    public function testCanSetAndGetDate(): void
    {
        $date = new DateTime();
        $this->assertNull($this->model->getDate());
        $this->assertSame($this->model, $this->model->setDate($date));
        $this->assertSame($date, $this->model->getDate());
    }

    public function testCanSetAndGetImboErrorCode(): void
    {
        $this->assertNull($this->model->getImboErrorCode());
        $this->assertSame($this->model, $this->model->setImboErrorCode(100));
        $this->assertSame(100, $this->model->getImboErrorCode());
    }

    public function testCanSetAndGetImageIdentifier(): void
    {
        $this->assertNull($this->model->getImageIdentifier());
        $this->assertSame($this->model, $this->model->setImageIdentifier('identifier'));
        $this->assertSame('identifier', $this->model->getImageIdentifier());
    }

    public function testCanCreateAnErrorBasedOnAnException(): void
    {
        $request = $this->createMock(Request::class);

        $exception = new RuntimeException('You wronged', Response::HTTP_BAD_REQUEST);

        $model = Error::createFromException($exception, $request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $model->getHttpCode());
        $this->assertSame('You wronged', $model->getErrorMessage());
        $this->assertNull($model->getImageIdentifier());
        $this->assertSame(Exception::ERR_UNSPECIFIED, $model->getImboErrorCode());
    }

    public function testWillUseCorrectImageIdentifierFromRequestWhenCreatingError(): void
    {
        $exception = new RuntimeException('You wronged', Response::HTTP_BAD_REQUEST);
        $exception->setImboErrorCode(123);

        $request = $this->createConfiguredMock(Request::class, [
            'getImage' => null,
            'getImageIdentifier' => 'imageIdentifier',
        ]);

        $model = Error::createFromException($exception, $request);

        $this->assertSame(123, $model->getImboErrorCode());
        $this->assertSame('imageIdentifier', $model->getImageIdentifier());
    }

    public function testWillUseImageIdentifierFromImageModelIfRequestHasAnImageWhenCreatingError(): void
    {
        $exception = new RuntimeException('You wronged', Response::HTTP_BAD_REQUEST);
        $exception->setImboErrorCode(123);

        $image = $this->createConfiguredMock(Image::class, [
            'getImageIdentifier' => 'imageId',
        ]);

        /** @var Request&MockObject */
        $request = $this->createConfiguredMock(Request::class, [
            'getImage' => $image,
        ]);
        $request->expects($this->never())->method('getImageIdentifier');

        $model = Error::createFromException($exception, $request);

        $this->assertSame(123, $model->getImboErrorCode());
        $this->assertSame('imageId', $model->getImageIdentifier());
    }

    public function testGetData(): void
    {
        $date = new DateTime();

        $this->model->setHttpCode(Response::HTTP_NOT_FOUND);
        $this->model->setErrorMessage('message');
        $this->model->setDate($date);
        $this->model->setImboErrorCode(100);
        $this->model->setImageIdentifier('identifier');

        $this->assertSame([
            'httpCode' => Response::HTTP_NOT_FOUND,
            'errorMessage' => 'message',
            'date' => $date,
            'imboErrorCode' => 100,
            'imageIdentifier' => 'identifier',
        ], $this->model->getData());
    }
}
