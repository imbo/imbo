<?php
namespace ImboUnitTest\Model;

use Imbo\Model\User;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Model\User
 */
class UserTest extends TestCase {
    /**
     * @var User
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() : void {
        $this->model = new User();
    }

    /**
     * @covers Imbo\Model\User::getUserId
     * @covers Imbo\Model\User::setUserId
     */
    public function testCanSetAndGetUserId() : void {
        $this->assertNull($this->model->getUserId());
        $this->assertSame($this->model, $this->model->setUserId('key'));
        $this->assertSame('key', $this->model->getUserId());
    }

    /**
     * @covers Imbo\Model\User::getNumImages
     * @covers Imbo\Model\User::setNumImages
     */
    public function testCanSetAndGetNumImages() : void {
        $this->assertNull($this->model->getNumImages());
        $this->assertSame($this->model, $this->model->setNumImages(10));
        $this->assertSame(10, $this->model->getNumImages());
    }

    /**
     * @covers Imbo\Model\User::getLastModified
     * @covers Imbo\Model\User::setLastModified
     */
    public function testCanSetAndGetLastModified() : void {
        $date = new DateTime();
        $this->assertNull($this->model->getLastModified());
        $this->assertSame($this->model, $this->model->setLastModified($date));
        $this->assertSame($date, $this->model->getLastModified());
    }

    /**
     * @covers Imbo\Model\User::getData
     */
    public function testGetData() : void {
        $date = new DateTime();
        $this->model
            ->setUserId('id')
            ->setNumImages(100)
            ->setLastModified($date);

        $this->assertSame([
            'id' => 'id',
            'numImages' => 100,
            'lastModified' => $date,
        ], $this->model->getData());
    }
}
