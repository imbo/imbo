<?php declare(strict_types=1);

namespace Imbo\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayModel::class)]
class ArrayModelTest extends TestCase
{
    private ArrayModel $model;

    protected function setUp(): void
    {
        $this->model = new ArrayModel();
    }

    public function testCanSetAndGetData(): void
    {
        $this->assertSame([], $this->model->getData());
        $this->assertSame($this->model, $this->model->setData(['key' => 'value']));
        $this->assertSame(['key' => 'value'], $this->model->getData());
    }

    public function testCanSetAndGetTitle(): void
    {
        $this->assertNull($this->model->getTitle());
        $this->assertSame($this->model, $this->model->setTitle('title'));
        $this->assertSame('title', $this->model->getTitle());
    }
}
