<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Http\Response\Formatter;

use Imbo\Image\Transformation\Convert;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
abstract class ImageFormatterTests extends \PHPUnit_Framework_TestCase {
    /**
     * @var ImageFormatterInterface
     */
    private $formatter;

    protected $transformation;
    protected $model;

    /**
     * Get the formatter we want to test
     *
     * @param Convert $convert The convert transformation to use with the formatter
     * @return ImageFormatterInterface
     */
    abstract protected function getFormatter(Convert $convert);

    /**
     * Get the expected content type for the formatter
     *
     * @return string
     */
    abstract protected function getExpectedContentType();

    /**
     * Set up the formatter
     */
    public function setUp() {
        $this->transformation = $this->getMockBuilder('Imbo\Image\Transformation\Convert')->disableOriginalConstructor()->getMock();
        $this->formatter = $this->getFormatter($this->transformation);
        $this->model = $this->getMock('Imbo\Model\Image');
    }

    /**
     * Tear down the formatter
     */
    public function tearDown() {
        $this->transformation = null;
        $this->formatter = null;
        $this->model = null;
    }

    public function testReturnsCorrectContentType() {
        $this->assertSame($this->getExpectedContentType(), $this->formatter->getContentType());
    }

    public function testDoesNotApplyTransformationWhenImageIsOfCorrectType() {
        $blob = 'image blob';
        $this->transformation->expects($this->never())->method('applyToImage');
        $this->model->expects($this->once())->method('getMimeType')->will($this->returnValue($this->getExpectedContentType()));
        $this->model->expects($this->once())->method('getBlob')->will($this->returnValue($blob));
        $this->assertSame($blob, $this->formatter->format($this->model));
    }

    public function testAppliesTransformationIfImageIsOfDifferentType() {
        $blob = 'image blob';
        $this->transformation->expects($this->once())->method('applyToImage')->with($this->model);
        $this->model->expects($this->once())->method('getMimeType')->will($this->returnValue('image/sometype'));
        $this->model->expects($this->once())->method('getBlob')->will($this->returnValue($blob));
        $this->assertSame($blob, $this->formatter->format($this->model));
    }
}
