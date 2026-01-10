<?php declare(strict_types=1);

namespace Imbo\Model;

use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(User::class)]
class UserTest extends TestCase
{
    private User $model;

    protected function setUp(): void
    {
        $this->model = new User();
    }

    public function testCanSetAndGetUserId(): void
    {
        $this->assertNull($this->model->getUserId());
        $this->assertSame($this->model, $this->model->setUserId('key'));
        $this->assertSame('key', $this->model->getUserId());
    }

    public function testCanSetAndGetNumImages(): void
    {
        $this->assertNull($this->model->getNumImages());
        $this->assertSame($this->model, $this->model->setNumImages(10));
        $this->assertSame(10, $this->model->getNumImages());
    }

    public function testCanSetAndGetLastModified(): void
    {
        $date = new DateTime();

        $this->assertNull($this->model->getLastModified());
        $this->assertSame($this->model, $this->model->setLastModified($date));
        $this->assertSame($date, $this->model->getLastModified());
    }

    public function testGetData(): void
    {
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
