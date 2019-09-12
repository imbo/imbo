<?php
namespace ImboUnitTest\Model;

use Imbo\Model\AccessRules;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Model\AccessRules
 */
class AccessRulesTest extends TestCase {
    /**
     * @var AccessRules
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() : void {
        $this->model = new AccessRules();
    }

    /**
     * @covers Imbo\Model\AccessRules::getRules
     * @covers Imbo\Model\AccessRules::setRules
     * @covers Imbo\Model\AccessRules::getData
     */
    public function testSetAndGetId() {
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
