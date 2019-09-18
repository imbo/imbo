<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException;
use Imbo\Model\Image;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Compress
 */
class CompressTest extends TestCase {
    private $transformation;

    public function setUp() : void {
        $this->transformation = new Compress();
    }

    /**
     * @covers ::transform
     */
    public function testThrowsExceptionOnMissingLevelParameter() : void {
        $this->expectExceptionObject(new TransformationException(
            'Missing required parameter: level',
            400
        ));
        $this->transformation->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testThrowsExceptionOnInvalidLevel() : void {
        $this->expectExceptionObject(new TransformationException(
            'level must be between 0 and 100',
            400
        ));
        $this->transformation->transform(['level' => 200]);
    }

    /**
     * @covers ::transform
     */
    public function testSetsOutputQualityCompression() : void {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setOutputQualityCompression')
            ->with(75)
            ->willReturnSelf();

        $this->transformation
            ->setImage($image)
            ->transform(['level' => 75]);
    }
}
