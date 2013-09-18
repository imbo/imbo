<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Storage;

use Imbo\Storage\ImageReaderAwareTrait;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Test suite\Unit tests
 */
class ImageReaderAwareTraitTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var ImageReaderAwareTrait
     */
    private $readerAware;

    /**
     * Set up the image reader aware class
     */
    public function setUp() {
        $this->readerAware = $this->getObjectForTrait('Imbo\Storage\ImageReaderAwareTrait');
    }

    /**
     * Tear down the image reader aware class
     */
    public function tearDown() {
        $this->readerAware = null;
    }

    /**
     * @covers Imbo\Storage\ImageReaderAwareTrait::setImageReader
     * @covers Imbo\Storage\ImageReaderAwareTrait::getImageReader
     */
    public function testCanSetAndGetReader() {
        $reader = $this->getMockBuilder('Imbo\Storage\ImageReader')
                       ->disableOriginalConstructor()
                       ->getMock();

        $this->readerAware->setImageReader($reader);
        $this->assertSame($reader, $this->readerAware->getImageReader());
    }
}
