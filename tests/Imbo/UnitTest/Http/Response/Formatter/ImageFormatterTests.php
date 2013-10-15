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

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
abstract class ImageFormatterTests extends \PHPUnit_Framework_TestCase {
    /**
     * @var ImageFormatterInterface
     */
    private $formatter;

    protected $model;

    /**
     * Get the formatter we want to test
     *
     * @return ImageFormatterInterface
     */
    abstract protected function getFormatter();

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
        $this->formatter = $this->getFormatter();
        $this->model = $this->getMock('Imbo\Model\Image');
    }

    /**
     * Tear down the formatter
     */
    public function tearDown() {
        $this->formatter = null;
        $this->model = null;
    }

    public function testReturnsCorrectContentType() {
        $this->assertSame($this->getExpectedContentType(), $this->formatter->getContentType());
    }

    public function testDoesNotApplyTransformationWhenImageIsOfCorrectType() {
        $blob = 'image blob';
        $this->model->expects($this->once())->method('getMimeType')->will($this->returnValue($this->getExpectedContentType()));
        $this->model->expects($this->once())->method('getBlob')->will($this->returnValue($blob));
        $this->model->expects($this->never())->method('transform');

        $this->assertSame($blob, $this->formatter->format($this->model));
    }

    public function testAppliesTransformationIfImageIsOfDifferentType() {
        $blob = 'image blob';
        $this->model->expects($this->once())->method('getMimeType')->will($this->returnValue('image/sometype'));
        $this->model->expects($this->once())->method('getBlob')->will($this->returnValue($blob));
        $this->model->expects($this->once())->method('transform')->with('convert', $this->isType('array'));
        $this->assertSame($blob, $this->formatter->format($this->model));
    }
}
