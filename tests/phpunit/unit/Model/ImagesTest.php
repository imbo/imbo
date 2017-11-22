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
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Model\Images
 * @group unit
 * @group models
 */
class ImagesTest extends TestCase {
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
     * @covers ::getImages
     * @covers ::setImages
     */
    public function testCanSetAndGetImages() {
        $images = [
            $this->createMock('Imbo\Model\Image'),
            $this->createMock('Imbo\Model\Image'),
            $this->createMock('Imbo\Model\Image'),
        ];
        $this->assertSame([], $this->model->getImages());
        $this->assertSame($this->model, $this->model->setImages($images));
        $this->assertSame($images, $this->model->getImages());
    }

    /**
     * @covers ::setFields
     * @covers ::getFields
     */
    public function testCanSetAndGetFields() {
        $this->assertSame([], $this->model->getFields());
        $this->assertSame($this->model, $this->model->setFields(['width', 'height']));
        $this->assertSame(['width', 'height'], $this->model->getFields());
    }

    /**
     * @covers ::setHits
     * @covers ::getHits
     */
    public function testCanSetAndGetHits() {
        $this->assertSame(0, $this->model->getHits(), 'Default value has changed');
        $this->assertSame($this->model, $this->model->setHits(10));
        $this->assertSame(10, $this->model->getHits());
    }

    /**
     * @covers ::setPage
     * @covers ::getPage
     */
    public function testCanSetAndGetPage() {
        $this->assertSame(1, $this->model->getPage(), 'Default value has changed');
        $this->assertSame($this->model, $this->model->setPage(10));
        $this->assertSame(10, $this->model->getPage());
    }

    /**
     * @covers ::setLimit
     * @covers ::getLimit
     */
    public function testCanSetAndGetLimit() {
        $this->assertSame(20, $this->model->getLimit(), 'Default value has changed');
        $this->assertSame($this->model, $this->model->setLimit(10));
        $this->assertSame(10, $this->model->getLimit());
    }

    /**
     * @covers ::getCount
     */
    public function testCanCountImages() {
        $this->assertSame(0, $this->model->getCount());
        $images = [
            $this->createMock('Imbo\Model\Image'),
            $this->createMock('Imbo\Model\Image'),
            $this->createMock('Imbo\Model\Image'),
        ];
        $this->model->setImages($images);
        $this->assertSame(3, $this->model->getCount());
    }

    /**
     * @covers ::getData
     */
    public function testGetData() {
        $images = [
            $this->createMock('Imbo\Model\Image'),
            $this->createMock('Imbo\Model\Image'),
            $this->createMock('Imbo\Model\Image'),
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
