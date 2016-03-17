<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Model;

use Imbo\Model\Error,
    Imbo\Exception\RuntimeException,
    Imbo\Exception,
    DateTime;

/**
 * @covers Imbo\Model\Error
 * @group unit
 * @group models
 */
class ErrorTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Error
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() {
        $this->model = new Error();
    }

    /**
     * Tear down the model
     */
    public function tearDown() {
        $this->model = null;
    }

    /**
     * @covers Imbo\Model\Error::getHttpCode
     * @covers Imbo\Model\Error::setHttpCode
     */
    public function testCanSetAndGetHttpCode() {
        $this->assertNull($this->model->getHttpCode());
        $this->assertSame($this->model, $this->model->setHttpCode(404));
        $this->assertSame(404, $this->model->getHttpCode());
    }

    /**
     * @covers Imbo\Model\Error::getErrorMessage
     * @covers Imbo\Model\Error::setErrorMessage
     */
    public function testCanSetAndGetErrorMessage() {
        $this->assertNull($this->model->getErrorMessage());
        $this->assertSame($this->model, $this->model->setErrorMessage('message'));
        $this->assertSame('message', $this->model->getErrorMessage());
    }

    /**
     * @covers Imbo\Model\Error::getDate
     * @covers Imbo\Model\Error::setDate
     */
    public function testCanSetAndGetDate() {
        $date = new DateTime();
        $this->assertNull($this->model->getDate());
        $this->assertSame($this->model, $this->model->setDate($date));
        $this->assertSame($date, $this->model->getDate());
    }

    /**
     * @covers Imbo\Model\Error::getImboErrorCode
     * @covers Imbo\Model\Error::setImboErrorCode
     */
    public function testCanSetAndGetImboErrorCode() {
        $this->assertNull($this->model->getImboErrorCode());
        $this->assertSame($this->model, $this->model->setImboErrorCode(100));
        $this->assertSame(100, $this->model->getImboErrorCode());
    }

    /**
     * @covers Imbo\Model\Error::getImageIdentifier
     * @covers Imbo\Model\Error::setImageIdentifier
     */
    public function testCanSetAndGetImageIdentifier() {
        $this->assertNull($this->model->getImageIdentifier());
        $this->assertSame($this->model, $this->model->setImageIdentifier('identifier'));
        $this->assertSame('identifier', $this->model->getImageIdentifier());
    }

    /**
     * @covers Imbo\Model\Error::createFromException
     */
    public function testCanCreateAnErrorBasedOnAnException() {
        $request = $this->getMock('Imbo\Http\Request\Request');

        $exception = new RuntimeException('You wronged', 400);

        $model = Error::createFromException($exception, $request);

        $this->assertSame(400, $model->getHttpCode());
        $this->assertSame('You wronged', $model->getErrorMessage());
        $this->assertNull($model->getImageIdentifier());
        $this->assertSame(Exception::ERR_UNSPECIFIED, $model->getImboErrorCode());
    }

    /**
     * @covers Imbo\Model\Error::createFromException
     */
    public function testWillUseCorrectImageIdentifierFromRequestWhenCreatingError() {
        $exception = new RuntimeException('You wronged', 400);
        $exception->setImboErrorCode(123);

        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getImage')->will($this->returnValue(null));
        $request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('imageIdentifier'));

        $model = Error::createFromException($exception, $request);

        $this->assertSame(123, $model->getImboErrorCode());
        $this->assertSame('imageIdentifier', $model->getImageIdentifier());
    }

    /**
     * @covers Imbo\Model\Error::createFromException
     */
    public function testWillUseImageIdentifierFromImageModelIfRequestHasAnImageWhenCreatingError() {
        $exception = new RuntimeException('You wronged', 400);
        $exception->setImboErrorCode(123);

        $request = $this->getMock('Imbo\Http\Request\Request');
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('imageId'));
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));
        $request->expects($this->never())->method('imageId');

        $model = Error::createFromException($exception, $request);

        $this->assertSame(123, $model->getImboErrorCode());
        $this->assertSame('imageId', $model->getImageIdentifier());
    }

    /**
     * @covers Imbo\Model\Error::getData
     */
    public function testGetData() {
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
