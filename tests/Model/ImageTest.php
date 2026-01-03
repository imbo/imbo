<?php declare(strict_types=1);
namespace Imbo\Model;

use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Image::class)]
class ImageTest extends TestCase
{
    private Image $image;

    public function setUp(): void
    {
        $this->image = new Image();
    }

    public function testCanSetAndGetMetadata(): void
    {
        $this->assertSame([], $this->image->getMetadata());
        $data = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $this->assertSame($this->image, $this->image->setMetadata($data));
        $this->assertSame($data, $this->image->getMetadata());
    }

    public function testCanSetAndGetMimeType(): void
    {
        $mimeType = 'image/png';
        $this->assertSame($this->image, $this->image->setMimeType($mimeType));
        $this->assertSame($mimeType, $this->image->getMimeType());
    }

    public function testMimeTypeOverride(): void
    {
        $this->assertSame($this->image, $this->image->setMimeType('image/x-png'));
        $this->assertSame('image/png', $this->image->getMimeType());
    }

    public function testCanSetAndGetBlob(): void
    {
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

    public function testCanSetAndGetExtension(): void
    {
        $extension = 'png';
        $this->assertSame($this->image, $this->image->setExtension($extension));
        $this->assertSame($extension, $this->image->getExtension());
    }

    public function testCanSetAndGetWidth(): void
    {
        $width = 123;
        $this->assertSame($this->image, $this->image->setWidth($width));
        $this->assertSame($width, $this->image->getWidth());
    }

    public function testCanSetAndGetHeight(): void
    {
        $height = 234;
        $this->assertSame($this->image, $this->image->setHeight($height));
        $this->assertSame($height, $this->image->getHeight());
    }

    public function testCanSetAndGetTheAddedDate(): void
    {
        $date = $this->createStub(DateTime::class);
        $this->assertNull($this->image->getAddedDate());
        $this->assertSame($this->image, $this->image->setAddedDate($date));
        $this->assertSame($date, $this->image->getAddedDate());
    }

    public function testCanSetAndGetTheUpdatedDate(): void
    {
        $date = $this->createStub(DateTime::class);
        $this->assertNull($this->image->getUpdatedDate());
        $this->assertSame($this->image, $this->image->setUpdatedDate($date));
        $this->assertSame($date, $this->image->getUpdatedDate());
    }

    public function testCanSetAndGetTheUser(): void
    {
        $this->assertNull($this->image->getUser());
        $this->assertSame($this->image, $this->image->setUser('christer'));
        $this->assertSame('christer', $this->image->getUser());
    }

    public function testCanSetAndGetTheImageIdentifier(): void
    {
        $this->assertNull($this->image->getImageIdentifier());
        $this->assertSame($this->image, $this->image->setImageIdentifier('identifier'));
        $this->assertSame('identifier', $this->image->getImageIdentifier());
    }


    public function testCanUpdateTransformationFlag(): void
    {
        $this->assertFalse($this->image->getHasBeenTransformed());
        $this->assertSame($this->image, $this->image->setHasBeenTransformed(true));
        $this->assertTrue($this->image->getHasBeenTransformed());
        $this->assertSame($this->image, $this->image->setHasBeenTransformed(false));
        $this->assertFalse($this->image->getHasBeenTransformed());
    }

    public function testCanSetAndGetTheOriginalChecksum(): void
    {
        $checksum = md5(__FILE__);
        $this->assertSame($this->image, $this->image->setOriginalChecksum($checksum));
        $this->assertSame($checksum, $this->image->getOriginalChecksum());
    }

    public function testCanSetAndGetOutputQualityCompression(): void
    {
        $compression = 50;
        $this->assertSame($this->image, $this->image->setOutputQualityCompression($compression));
        $this->assertSame($compression, $this->image->getOutputQualityCompression());
    }

    public function testGetData(): void
    {
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
