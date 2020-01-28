<?php declare(strict_types=1);
namespace Imbo\Model;

use PHPUnit\Framework\TestCase;
use DateTime;

/**
 * @coversDefaultClass Imbo\Model\User
 */
class UserTest extends TestCase {
    private $model;

    public function setUp() : void {
        $this->model = new User();
    }

    /**
     * @covers ::getUserId
     * @covers ::setUserId
     */
    public function testCanSetAndGetUserId() : void {
        $this->assertNull($this->model->getUserId());
        $this->assertSame($this->model, $this->model->setUserId('key'));
        $this->assertSame('key', $this->model->getUserId());
    }

    /**
     * @covers ::getNumImages
     * @covers ::setNumImages
     */
    public function testCanSetAndGetNumImages() : void {
        $this->assertNull($this->model->getNumImages());
        $this->assertSame($this->model, $this->model->setNumImages(10));
        $this->assertSame(10, $this->model->getNumImages());
    }

    /**
     * @covers ::getLastModified
     * @covers ::setLastModified
     */
    public function testCanSetAndGetLastModified() : void {
        $date = new DateTime();

        $this->assertNull($this->model->getLastModified());
        $this->assertSame($this->model, $this->model->setLastModified($date));
        $this->assertSame($date, $this->model->getLastModified());
    }

    /**
     * @covers ::getData
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
