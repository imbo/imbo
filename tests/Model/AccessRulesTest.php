<?php declare(strict_types=1);
namespace Imbo\Model;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Model\AccessRules
 */
class AccessRulesTest extends TestCase {
    private $model;

    public function setUp() : void {
        $this->model = new AccessRules();
    }

    /**
     * @covers ::getRules
     * @covers ::setRules
     * @covers ::getData
     */
    public function testSetAndGetId() : void {
        $rules = [
            ['id' => 1, 'group' => 'group', 'users' => ['user']],
            ['id' => 2, 'resources' => ['image.get', 'image.head'], 'users' => ['user']],
        ];
        $this->assertSame([], $this->model->getRules());
        $this->assertSame([], $this->model->getData());
        $this->assertSame($this->model, $this->model->setRules($rules));
        $this->assertSame($rules, $this->model->getRules());
        $this->assertSame($rules, $this->model->getData());
    }
}
