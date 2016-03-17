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

use Imbo\Model\Images;

/**
 * @covers Imbo\Model\Images
 * @group unit
 * @group models
 */
class ImagesTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Images
     */
    private $model;

    /**
     * Set up the model
     */
    public function setUp() {
        $this->model = new Images();
    }

    /**
     * Tear down the model
     */
    public function tearDown() {
        $this->model = null;
    }

    /**
     * @covers Imbo\Model\Images::getImages
     * @covers Imbo\Model\Images::setImages
     */
    public function testCanSetAndGetImages() {
        $images = [
            $this->getMock('Imbo\Model\Image'),
            $this->getMock('Imbo\Model\Image'),
            $this->getMock('Imbo\Model\Image'),
        ];
        $this->assertSame([], $this->model->getImages());
        $this->assertSame($this->model, $this->model->setImages($images));
        $this->assertSame($images, $this->model->getImages());
    }

    /**
     * @covers Imbo\Model\Images::setFields
     * @covers Imbo\Model\Images::getFields
     */
    public function testCanSetAndGetFields() {
        $this->assertSame([], $this->model->getFields());
        $this->assertSame($this->model, $this->model->setFields(['width', 'height']));
        $this->assertSame(['width', 'height'], $this->model->getFields());
    }

    /**
     * @covers Imbo\Model\Images::setHits
     * @covers Imbo\Model\Images::getHits
     */
    public function testCanSetAndGetHits() {
        $this->assertNull($this->model->getHits());
        $this->assertSame($this->model, $this->model->setHits(10));
        $this->assertSame(10, $this->model->getHits());
    }

    /**
     * @covers Imbo\Model\Images::setPage
     * @covers Imbo\Model\Images::getPage
     */
    public function testCanSetAndGetPage() {
        $this->assertNull($this->model->getPage());
        $this->assertSame($this->model, $this->model->setPage(10));
        $this->assertSame(10, $this->model->getPage());
    }

    /**
     * @covers Imbo\Model\Images::setLimit
     * @covers Imbo\Model\Images::getLimit
     */
    public function testCanSetAndGetLimit() {
        $this->assertNull($this->model->getLimit());
        $this->assertSame($this->model, $this->model->setLimit(10));
        $this->assertSame(10, $this->model->getLimit());
    }

    /**
     * @covers Imbo\Model\Images::getCount
     */
    public function testCanCountImages() {
        $this->assertSame(0, $this->model->getCount());
        $images = [
            $this->getMock('Imbo\Model\Image'),
            $this->getMock('Imbo\Model\Image'),
            $this->getMock('Imbo\Model\Image'),
        ];
        $this->model->setImages($images);
        $this->assertSame(3, $this->model->getCount());
    }

    /**
     * @covers Imbo\Model\Images::getData
     */
    public function testGetData() {
        $images = [
            $this->getMock('Imbo\Model\Image'),
            $this->getMock('Imbo\Model\Image'),
            $this->getMock('Imbo\Model\Image'),
        ];
        $fields = ['width', 'height'];

        $this->model
            ->setImages($images)
            ->setFields($fields)
            ->setHits(10)
            ->setLimit(11)
            ->setPage(12);

        $this->assertSame([
            'images' => $images,
            'fields' => $fields,
            'count' => 3,
            'hits' => 10,
            'limit' => 11,
            'page' => 12,
        ], $this->model->getData());
    }
}
