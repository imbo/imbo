<?php declare(strict_types=1);
namespace Imbo\Model;

use PHPUnit\Framework\TestCase;
use DateTime;

/**
 * @coversDefaultClass Imbo\Model\Image
 */
class ImageTest extends TestCase {
    private $image;

    public function setUp() : void {
        $this->image = new Image();
    }

    /**
     * @covers ::setMetadata
     * @covers ::getMetadata
     */
    public function testCanSetAndGetMetadata() : void {
        $this->assertSame([], $this->image->getMetadata());
        $data = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $this->assertSame($this->image, $this->image->setMetadata($data));
        $this->assertSame($data, $this->image->getMetadata());
    }

    /**
     * @covers ::setMimeType
     * @covers ::getMimeType
     */
    public function testCanSetAndGetMimeType() : void {
        $mimeType = 'image/png';
        $this->assertSame($this->image, $this->image->setMimeType($mimeType));
        $this->assertSame($mimeType, $this->image->getMimeType());
    }

    /**
     * @covers ::setMimeType
     */
    public function testMimeTypeOverride() : void {
        $this->assertSame($this->image, $this->image->setMimeType('image/x-png'));
        $this->assertSame('image/png', $this->image->getMimeType());
    }

    /**
     * @covers ::setBlob
     * @covers ::getBlob
     * @covers ::getFilesize
     * @covers ::setFilesize
     * @covers ::getChecksum
     * @covers ::setChecksum
     */
    public function testCanSetAndGetBlob() : void {
        $blob = 'some string';
        $hash = md5($blob);
        $this->assertSame($this->image, $this->image->setBlob($blob));
        $this->assertSame($blob, $this->image->getBlob());
        $this->assertSame(11, $this->image->getFilesize());
        $this->assertSame($hash, $this->image->getChecksum());

        $blob = 'some other string';
        $hash = md5($blob);
        $this->assertSame($this->image, $this->image->setBlob($blob));
        $this->assertSame($blob, $this->image->getBlob());
        $this->assertSame(17, $this->image->getFilesize());
        $this->assertSame($hash, $this->image->getChecksum());
    }

    /**
     * @covers ::setExtension
     * @covers ::getExtension
     */
    public function testCanSetAndGetExtension() : void {
        $extension = 'png';
        $this->assertSame($this->image, $this->image->setExtension($extension));
        $this->assertSame($extension, $this->image->getExtension());
    }

    /**
     * @covers ::setWidth
     * @covers ::getWidth
     */
    public function testCanSetAndGetWidth() : void {
        $width = 123;
        $this->assertSame($this->image, $this->image->setWidth($width));
        $this->assertSame($width, $this->image->getWidth());
    }

    /**
     * @covers ::setHeight
     * @covers ::getHeight
     */
    public function testCanSetAndGetHeight() : void {
        $height = 234;
        $this->assertSame($this->image, $this->image->setHeight($height));
        $this->assertSame($height, $this->image->getHeight());
    }

    /**
     * @covers ::setAddedDate
     * @covers ::getAddedDate
     */
    public function testCanSetAndGetTheAddedDate() : void {
        $date = $this->createMock(DateTime::class);
        $this->assertNull($this->image->getAddedDate());
        $this->assertSame($this->image, $this->image->setAddedDate($date));
        $this->assertSame($date, $this->image->getAddedDate());
    }

    /**
     * @covers ::setUpdatedDate
     * @covers ::getUpdatedDate
     */
    public function testCanSetAndGetTheUpdatedDate() : void {
        $date = $this->createMock(DateTime::class);
        $this->assertNull($this->image->getUpdatedDate());
        $this->assertSame($this->image, $this->image->setUpdatedDate($date));
        $this->assertSame($date, $this->image->getUpdatedDate());
    }

    /**
     * @covers ::setUser
     * @covers ::getUser
     */
    public function testCanSetAndGetTheUser() : void {
        $this->assertNull($this->image->getUser());
        $this->assertSame($this->image, $this->image->setUser('christer'));
        $this->assertSame('christer', $this->image->getUser());
    }

    /**
     * @covers ::setImageIdentifier
     * @covers ::getImageIdentifier
     */
    public function testCanSetAndGetTheImageIdentifier() : void {
        $this->assertNull($this->image->getImageIdentifier());
        $this->assertSame($this->image, $this->image->setImageIdentifier('identifier'));
        $this->assertSame('identifier', $this->image->getImageIdentifier());
    }


    /**
     * @covers ::getHasBeenTransformed
     * @covers ::setHasBeenTransformed
     */
    public function testCanUpdateTransformationFlag() : void {
        $this->assertFalse($this->image->getHasBeenTransformed());
        $this->assertSame($this->image, $this->image->setHasBeenTransformed(true));
        $this->assertTrue($this->image->getHasBeenTransformed());
        $this->assertSame($this->image, $this->image->setHasBeenTransformed(false));
        $this->assertFalse($this->image->getHasBeenTransformed());
    }

    /**
     * @covers ::setOriginalChecksum
     * @covers ::getOriginalChecksum
     */
    public function testCanSetAndGetTheOriginalChecksum() : void {
        $checksum = md5(__FILE__);
        $this->assertSame($this->image, $this->image->setOriginalChecksum($checksum));
        $this->assertSame($checksum, $this->image->getOriginalChecksum());
    }

    /**
     * @covers ::setOutputQualityCompression
     * @covers ::getOutputQualityCompression
     */
    public function testCanSetAndGetOutputQualityCompression() : void {
        $compression = 50;
        $this->assertSame($this->image, $this->image->setOutputQualityCompression($compression));
        $this->assertSame($compression, $this->image->getOutputQualityCompression());
    }

    /**
     * @covers ::getData
     */
    public function testGetData() : void {
        $metadata = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $mimeType = 'image/png';
        $blob = 'some string';
        $filesize = strlen($blob);
        $checksum = md5($blob);
        $extension = 'png';
        $width = 123;
        $height = 234;
        $added = new DateTime();
        $updated = new DateTime();
        $user = 'christer';
        $identifier = 'identifier';
        $compression = 75;

        $this->image
            ->setMetadata($metadata)
            ->setMimeType($mimeType)
            ->setBlob($blob)
            ->setExtension($extension)
            ->setWidth($width)
            ->setHeight($height)
            ->setAddedDate($added)
            ->setUpdatedDate($updated)
            ->setUser($user)
            ->setImageIdentifier($identifier)
            ->setHasBeenTransformed(true)
            ->setOriginalChecksum($checksum)
            ->setOutputQualityCompression($compression);

        $this->assertSame([
            'filesize'                 => $filesize,
            'mimeType'                 => $mimeType,
            'extension'                => $extension,
            'metadata'                 => $metadata,
            'width'                    => $width,
            'height'                   => $height,
            'addedDate'                => $added,
            'updatedDate'              => $updated,
            'user'                     => $user,
            'imageIdentifier'          => $identifier,
            'checksum'                 => $checksum,
            'originalChecksum'         => $checksum,
            'hasBeenTransformed'       => true,
            'outputQualityCompression' => $compression,
        ], $this->image->getData());
    }
}
