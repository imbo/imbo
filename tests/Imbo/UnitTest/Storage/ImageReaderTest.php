<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest;

use Imbo\Storage\ImageReader,
    Imbo\Storage\StorageInterface;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Test suite\Unit tests
 */
class ImageReaderTest extends \PHPUnit_Framework_TestCase {
    /**
     * Image reader instance
     * 
     * @var ImageReader
     */
    private $imageReader;

    /**
     * Public key to use for testing
     * 
     * @var string
     */
    private $publicKey = 'pubKey';

    /**
     * Storage mock
     * 
     * @var StorageInterface
     */
    private $storageMock;

    /**
     * Set up the image reader instance
     */
    public function setUp() {
        $this->storageMock = $this->getStorageMock();
        $this->imageReader = new ImageReader(
            $this->publicKey,
            $this->storageMock
        );
    }

    /**
     * Tear down the image reader instance
     */
    public function tearDown() {
        $this->imageReader = null;
    }

    /**
     * @covers Imbo\Storage\ImageReader::getImage
     */
    public function testCanGetImage() {
        $this->storageMock->expects($this->once())
                          ->method('getImage')
                          ->with('pubKey', 'identifier')
                          ->will($this->returnValue('response'));

        $this->assertEquals('response', $this->imageReader->getImage('identifier'));
    }

    /**
     * @covers Imbo\Storage\ImageReader::imageExists
     */
    public function testCanCheckIfImageExists() {
        $this->storageMock->expects($this->once())
                          ->method('imageExists')
                          ->with('pubKey', 'identifier')
                          ->will($this->returnValue(true));

        $this->assertTrue($this->imageReader->imageExists('identifier'));
    }

    /**
     * Get storage mock
     * 
     * @return StorageInterface
     */
    protected function getStorageMock() {
        return $this->getMock('Imbo\Storage\StorageInterface');
    }
}
