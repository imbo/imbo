<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Model;

use Imbo\Model\Error,
    DateTime;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 * @covers Imbo\Model\Error
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
}
