<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Image\Transformation;

use Imbo\Image\Transformation\Convert;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @covers Imbo\Image\Transformation\Convert
 */
class ConvertTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Convert
     */
    private $transformation;

    /**
     * The extension to use for testing
     *
     * @var string
     */
    private $extension = 'png';

    /**
     * Set up the transformation instance
     */
    public function setUp() {
        $this->transformation = new Convert(array('type' => $this->extension));
    }

    /**
     * Tear down the transformation instance
     */
    public function tearDown() {
        $this->transformation = null;
    }

    /**
     * @covers Imbo\Image\Transformation\Convert::applyToImage
     */
    public function testConvertToSameTypeAsImage() {
        $image = $this->getMock('Imbo\Image\Image');
        $image->expects($this->once())->method('getExtension')->will($this->returnValue($this->extension));
        $image->expects($this->never())->method('getBlob');

        $this->transformation->applyToImage($image);
    }
}
