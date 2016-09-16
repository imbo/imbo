<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Http\Response;

use Imbo\Http\Response\Response,
    Imbo\Exception,
    Imbo\Exception\RuntimeException,
    DateTime,
    DateTimeZone;

/**
 * @covers Imbo\Http\Response\Response
 * @group unit
 * @group http
 */
class ResponseTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Response
     */
    private $response;

    /**
     * Set up the response
     */
    public function setUp() {
        $this->response = new Response();
    }

    /**
     * Tear down the response
     */
    public function tearDown() {
        $this->response = null;
    }

    /**
     * @covers Imbo\Http\Response\Response::setModel
     * @covers Imbo\Http\Response\Response::getModel
     */
    public function testCanSetAndGetModel() {
        $model = $this->getMock('Imbo\Model\ModelInterface');
        $this->assertNull($this->response->getModel());
        $this->assertSame($this->response, $this->response->setModel($model));
        $this->assertSame($model, $this->response->getModel());
        $this->assertSame($this->response, $this->response->setModel(null));
        $this->assertNull($this->response->getModel());
    }

    /**
     * @covers Imbo\Http\Response\Response::setModel
     * @covers Imbo\Http\Response\Response::setNotModified
     */
    public function testRemovesModelWhenMarkedAsNotModified() {
        $model = $this->getMock('Imbo\Model\ModelInterface');
        $this->assertSame($this->response, $this->response->setModel($model));
        $this->assertSame($this->response, $this->response->setNotModified());
        $this->assertSame(304, $this->response->getStatusCode());
        $this->assertNull($this->response->getModel());
    }

    /**
     * @covers Imbo\Http\Response\Response::setError
     */
    public function testUpdatesResponseWhenSettingAnErrorModel() {
        $message = 'You wronged';
        $code = 404;
        $imboErrorCode = 123;
        $date = new DateTime('@1361614522', new DateTimeZone('UTC'));

        $error = $this->getMock('Imbo\Model\Error');
        $error->expects($this->once())->method('getHttpCode')->will($this->returnValue($code));
        $error->expects($this->once())->method('getImboErrorCode')->will($this->returnValue($imboErrorCode));
        $error->expects($this->once())->method('getErrorMessage')->will($this->returnValue($message));
        $error->expects($this->once())->method('getDate')->will($this->returnValue($date));

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
