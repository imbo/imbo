<?php
namespace ImboUnitTest\Image\Transformation;

use Imbo\Image\Transformation\Convert;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Convert
 */
class ConvertTest extends TestCase {
    /**
     * @var Convert
     */
    private $transformation;

    /**
     * Set up the transformation instance
     */
    public function setUp() : void {
        $this->transformation = new Convert();
    }

    /**
     * @covers Imbo\Image\Transformation\Convert::transform
     */
    public function testWillNotConvertImageIfNotNeeded() : void {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getExtension')->will($this->returnValue('png'));
        $image->expects($this->never())->method('getBlob');

        $this->transformation->setImage($image);
        $this->transformation->transform(['type' => 'png']);
    }
}
