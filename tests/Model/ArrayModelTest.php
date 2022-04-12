<?php declare(strict_types=1);
namespace Imbo\Model;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Model\ArrayModel
 */
class ArrayModelTest extends TestCase
{
    private $model;

    public function setUp(): void
    {
        $this->model = new ArrayModel();
    }

    /**
     * @covers ::getData
     * @covers ::setData
     */
    public function testCanSetAndGetData(): void
    {
        $this->assertSame([], $this->model->getData());
        $this->assertSame($this->model, $this->model->setData(['key' => 'value']));
        $this->assertSame(['key' => 'value'], $this->model->getData());
    }

    /**
     * @covers ::setTitle
     * @covers ::getTitle
     */
    public function testCanSetAndGetTitle(): void
    {
        $this->assertNull($this->model->getTitle());
        $this->assertSame($this->model, $this->model->setTitle('title'));
        $this->assertSame('title', $this->model->getTitle());
    }
}
