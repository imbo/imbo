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
use Imbo\Exception\TransformationException;
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
    public function setUp() : void {
        $this->transformation = new Compress();
    }

    public function testThrowsExceptionOnMissingLevelParameter() {
        $this->expectExceptionObject(new TransformationException(
            'Missing required parameter: level',
            400
        ));
        $this->transformation->transform([]);
    }

    public function testThrowsExceptionOnInvalidLevel() {
        $this->expectExceptionObject(new TransformationException(
            'level must be between 0 and 100',
            400
        ));
        $this->transformation->transform(['level' => 200]);
    }
}
