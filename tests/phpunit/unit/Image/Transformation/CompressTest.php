<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Image\Transformation;

use Imbo\Image\Transformation\Compress;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Image\Transformation\Compress
 * @group unit
 * @group transformations
 */
class CompressTest extends TestCase {
    /**
     * @var Compress
     */
    private $transformation;

    /**
     * Set up the transformation
     */
    public function setUp() {
        $this->transformation = new Compress();
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionMessage Missing required parameter: level
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionOnMissingLevelParameter() {
        $this->transformation->transform([]);
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionMessage level must be between 0 and 100
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionOnInvalidLevel() {
        $this->transformation->transform(['level' => 200]);
    }
}
