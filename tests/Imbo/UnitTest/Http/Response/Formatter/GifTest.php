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

use Imbo\Http\Response\Formatter\Gif;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class GifTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Gif
     */
    private $formatter;

    /**
     * Set up the formatter
     */
    public function setUp() {
        $this->formatter = new Gif();
    }

    /**
     * Tear down the formatter
     */
    public function tearDown() {
        $this->formatter = null;
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Gif::getContentType
     */
    public function testReturnsCorrectContentType() {
        $this->assertSame('image/gif', $this->formatter->getContentType());
    }

    /**
     * @covers Imbo\Http\Response\Formatter\Gif::formatImage
     */
    public function testCanTransformModelsToGif() {

    }

    /**
     * @covers Imbo\Http\Response\Formatter\Gif::formatImage
     */
    public function testDoesNotTransformImagesThatIsAlreadyInTheSameFormat() {

    }
}
