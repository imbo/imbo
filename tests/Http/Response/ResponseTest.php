<?php declare(strict_types=1);

namespace Imbo\Http\Response;

use DateTime;
use DateTimeZone;
use Imbo\Model\Error;
use Imbo\Model\ModelInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Response::class)]
class ResponseTest extends TestCase
{
    private Response $response;

    protected function setUp(): void
    {
        $this->response = new Response();
    }

    public function testCanSetAndGetModel(): void
    {
        $model = $this->createStub(ModelInterface::class);
        $this->assertNull($this->response->getModel());
        $this->assertSame($this->response, $this->response->setModel($model));
        $this->assertSame($model, $this->response->getModel());
        $this->assertSame($this->response, $this->response->setModel(null));
        $this->assertNull($this->response->getModel());
    }

    public function testUpdatesResponseWhenSettingAnErrorModel(): void
    {
        $message = 'You wronged';
        $code = Response::HTTP_NOT_FOUND;
        $imboErrorCode = '123';
        $date = new DateTime('@1361614522', new DateTimeZone('UTC'));

        $error = $this->createConfiguredStub(Error::class, [
            'getHttpCode' => $code,
            'getImboErrorCode' => (int) $imboErrorCode,
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
