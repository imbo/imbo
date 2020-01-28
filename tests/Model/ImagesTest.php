<?php declare(strict_types=1);
namespace Imbo\Model;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Model\Images
 */
class ImagesTest extends TestCase {
    private $model;

    public function setUp() : void {
        $this->model = new Images();
    }

    /**
     * @covers ::getImages
     * @covers ::setImages
     */
    public function testCanSetAndGetImages() : void {
        $images = [
            $this->createMock(Image::class),
            $this->createMock(Image::class),
            $this->createMock(Image::class),
        ];
        $this->assertSame([], $this->model->getImages());
        $this->assertSame($this->model, $this->model->setImages($images));
        $this->assertSame($images, $this->model->getImages());
    }

    /**
     * @covers ::setFields
     * @covers ::getFields
     */
    public function testCanSetAndGetFields() : void {
        $this->assertSame([], $this->model->getFields());
        $this->assertSame($this->model, $this->model->setFields(['width', 'height']));
        $this->assertSame(['width', 'height'], $this->model->getFields());
    }

    /**
     * @covers ::setHits
     * @covers ::getHits
     */
    public function testCanSetAndGetHits() : void {
        $this->assertSame(0, $this->model->getHits(), 'Default value has changed');
        $this->assertSame($this->model, $this->model->setHits(10));
        $this->assertSame(10, $this->model->getHits());
    }

    /**
     * @covers ::setPage
     * @covers ::getPage
     */
    public function testCanSetAndGetPage() : void {
        $this->assertSame(1, $this->model->getPage(), 'Default value has changed');
        $this->assertSame($this->model, $this->model->setPage(10));
        $this->assertSame(10, $this->model->getPage());
    }

    /**
     * @covers ::setLimit
     * @covers ::getLimit
     */
    public function testCanSetAndGetLimit() : void {
        $this->assertSame(20, $this->model->getLimit(), 'Default value has changed');
        $this->assertSame($this->model, $this->model->setLimit(10));
        $this->assertSame(10, $this->model->getLimit());
    }

    /**
     * @covers ::getCount
     */
    public function testCanCountImages() : void {
        $this->assertSame(0, $this->model->getCount());
        $images = [
            $this->createMock(Image::class),
            $this->createMock(Image::class),
            $this->createMock(Image::class),
        ];
        $this->model->setImages($images);
        $this->assertSame(3, $this->model->getCount());
    }

    /**
     * @covers ::getData
     */
    public function testGetData() : void {
        $images = [
            $this->createMock(Image::class),
            $this->createMock(Image::class),
            $this->createMock(Image::class),
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
