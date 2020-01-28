<?php declare(strict_types=1);
namespace Imbo\Model;

use Imbo\Http\Request\Request;
use Imbo\Exception\RuntimeException;
use Imbo\Exception;
use PHPUnit\Framework\TestCase;
use DateTime;

/**
 * @coversDefaultClass Imbo\Model\Error
 */
class ErrorTest extends TestCase {
    private $model;

    public function setUp() : void {
        $this->model = new Error();
    }

    /**
     * @covers ::getHttpCode
     * @covers ::setHttpCode
     */
    public function testCanSetAndGetHttpCode() : void {
        $this->assertNull($this->model->getHttpCode());
        $this->assertSame($this->model, $this->model->setHttpCode(404));
        $this->assertSame(404, $this->model->getHttpCode());
    }

    /**
     * @covers ::getErrorMessage
     * @covers ::setErrorMessage
     */
    public function testCanSetAndGetErrorMessage() : void {
        $this->assertNull($this->model->getErrorMessage());
        $this->assertSame($this->model, $this->model->setErrorMessage('message'));
        $this->assertSame('message', $this->model->getErrorMessage());
    }

    /**
     * @covers ::getDate
     * @covers ::setDate
     */
    public function testCanSetAndGetDate() : void {
        $date = new DateTime();
        $this->assertNull($this->model->getDate());
        $this->assertSame($this->model, $this->model->setDate($date));
        $this->assertSame($date, $this->model->getDate());
    }

    /**
     * @covers ::getImboErrorCode
     * @covers ::setImboErrorCode
     */
    public function testCanSetAndGetImboErrorCode() : void {
        $this->assertNull($this->model->getImboErrorCode());
        $this->assertSame($this->model, $this->model->setImboErrorCode(100));
        $this->assertSame(100, $this->model->getImboErrorCode());
    }

    /**
     * @covers ::getImageIdentifier
     * @covers ::setImageIdentifier
     */
    public function testCanSetAndGetImageIdentifier() : void {
        $this->assertNull($this->model->getImageIdentifier());
        $this->assertSame($this->model, $this->model->setImageIdentifier('identifier'));
        $this->assertSame('identifier', $this->model->getImageIdentifier());
    }

    /**
     * @covers ::createFromException
     */
    public function testCanCreateAnErrorBasedOnAnException() : void {
        $request = $this->createMock(Request::class);

        $exception = new RuntimeException('You wronged', 400);

        $model = Error::createFromException($exception, $request);

        $this->assertSame(400, $model->getHttpCode());
        $this->assertSame('You wronged', $model->getErrorMessage());
        $this->assertNull($model->getImageIdentifier());
        $this->assertSame(Exception::ERR_UNSPECIFIED, $model->getImboErrorCode());
    }

    /**
     * @covers ::createFromException
     */
    public function testWillUseCorrectImageIdentifierFromRequestWhenCreatingError() : void {
        $exception = new RuntimeException('You wronged', 400);
        $exception->setImboErrorCode(123);

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getImage')->will($this->returnValue(null));
        $request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('imageIdentifier'));

        $model = Error::createFromException($exception, $request);

        $this->assertSame(123, $model->getImboErrorCode());
        $this->assertSame('imageIdentifier', $model->getImageIdentifier());
    }

    /**
     * @covers ::createFromException
     */
    public function testWillUseImageIdentifierFromImageModelIfRequestHasAnImageWhenCreatingError() : void {
        $exception = new RuntimeException('You wronged', 400);
        $exception->setImboErrorCode(123);

        $request = $this->createMock(Request::class);
        $image = $this->createMock(Image::class);
        $image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('imageId'));
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $request->expects($this->never())->method('getImageIdentifier');

        $model = Error::createFromException($exception, $request);

        $this->assertSame(123, $model->getImboErrorCode());
        $this->assertSame('imageId', $model->getImageIdentifier());
    }

    /**
     * @covers ::getData
     */
    public function testGetData() : void {
        $date = new DateTime();

        $this->model->setHttpCode(404);
        $this->model->setErrorMessage('message');
        $this->model->setDate($date);
        $this->model->setImboErrorCode(100);
        $this->model->setImageIdentifier('identifier');

        $this->assertSame([
            'httpCode' => 404,
            'errorMessage' => 'message',
            'date' => $date,
            'imboErrorCode' => 100,
            'imageIdentifier' => 'identifier',
        ], $this->model->getData());
    }
}
