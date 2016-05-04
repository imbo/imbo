<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Model;

use Imbo\Model\AccessRules;

/**
 * @covers Imbo\Model\AccessRules
 * @group unit
 * @group models
 */
class AccessRulesTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var AccessRules
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() {
        $this->model = new AccessRules();
    }

    /**
     * Tear down the model
     */
    public function tearDown() {
        $this->model = null;
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
