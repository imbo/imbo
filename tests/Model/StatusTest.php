<?php declare(strict_types=1);
namespace Imbo\Model;

use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Status::class)]
class StatusTest extends TestCase
{
    private Status $model;

    public function setUp(): void
    {
        $this->model = new Status();
    }

    public function testCanSetAndGetDate(): void
    {
        $date = new DateTime();

        $this->assertNull($this->model->getDate());
        $this->assertSame($this->model, $this->model->setDate($date));
        $this->assertSame($date, $this->model->getDate());
    }

    public function testCanSetAndGetDatabaseStatus(): void
    {
        $this->assertNull($this->model->getDatabaseStatus());
        $this->assertSame($this->model, $this->model->setDatabaseStatus(true));
        $this->assertTrue($this->model->getDatabaseStatus());
    }

    public function testCanSetAndGetStorageStatus(): void
    {
        $this->assertNull($this->model->getStorageStatus());
        $this->assertSame($this->model, $this->model->setStorageStatus(true));
        $this->assertTrue($this->model->getStorageStatus());
    }

    public function testGetData(): void
    {
        $date = new DateTime();

        $this->model
            ->setDate($date)
            ->setDatabaseStatus(true)
            ->setStorageStatus(true);

        $this->assertSame([
            'date' => $date,
            'database' => true,
            'storage' => true,
        ], $this->model->getData());
    }
}
