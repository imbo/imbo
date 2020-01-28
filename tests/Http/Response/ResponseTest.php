<?php declare(strict_types=1);
namespace Imbo\Http\Response;

use DateTime;
use DateTimeZone;
use Imbo\Model\Error;
use Imbo\Model\ModelInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Http\Response\Response
 */
class ResponseTest extends TestCase {
    private $response;

    public function setUp() : void {
        $this->response = new Response();
    }

    /**
     * @covers ::setModel
     * @covers ::getModel
     */
    public function testCanSetAndGetModel() : void {
        $model = $this->createMock(ModelInterface::class);
        $this->assertNull($this->response->getModel());
        $this->assertSame($this->response, $this->response->setModel($model));
        $this->assertSame($model, $this->response->getModel());
        $this->assertSame($this->response, $this->response->setModel(null));
        $this->assertNull($this->response->getModel());
    }

    /**
     * @covers ::setModel
     * @covers ::setNotModified
     */
    public function testRemovesModelWhenMarkedAsNotModified() : void {
        $model = $this->createMock(ModelInterface::class);
        $this->assertSame($this->response, $this->response->setModel($model));
        $this->assertSame($this->response, $this->response->setNotModified());
        $this->assertSame(304, $this->response->getStatusCode());
        $this->assertNull($this->response->getModel());
    }

    /**
     * @covers ::setError
     */
    public function testUpdatesResponseWhenSettingAnErrorModel() : void {
        $message = 'You wronged';
        $code = 404;
        $imboErrorCode = '123';
        $date = new DateTime('@1361614522', new DateTimeZone('UTC'));

        $error = $this->createConfiguredMock(Error::class, [
            'getHttpCode' => $code,
            'getImboErrorCode' => $imboErrorCode,
            'getErrorMessage' => $message,
            'getDate' => $date,
        ]);

        $this->response->headers->set('ETag', '"sometag"');
        $this->response->setLastModified(new DateTime('now', new DateTimeZone('UTC')));
        $this->response->setError($error);

        $this->assertSame($code, $this->response->getStatusCode());
        $this->assertSame($message, $this->response->headers->get('X-Imbo-Error-Message'));
        $this->assertSame($imboErrorCode, $this->response->headers->get('X-Imbo-Error-InternalCode'));
        $this->assertNull($this->response->headers->get('ETag'));
        $this->assertNull($this->response->getLastModified());
    }
}
