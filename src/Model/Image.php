<?php declare(strict_types=1);
namespace Imbo\Model;

use DateTime;

class Image implements ModelInterface
{
    /**
     * Mapping for some mime types
     *
     * @var array<string,string>
     */
    public static array $mimeTypeMapping = [
        'image/x-png'  => 'image/png',
        'image/x-jpeg' => 'image/jpeg',
        'image/x-gif'  => 'image/gif',
    ];

    /**
     * Size of the file
     */
    private int $filesize;

    /**
     * Mime type of the image
     */
    private string $mimeType;

    /**
     * Extension of the file without the dot
     */
    private string $extension;

    /**
     * Blob containing the image itself
     */
    private string $blob;

    /**
     * The metadata attached to this image
     *
     * @var array
     */
    private array $metadata = [];

    /**
     * Width of the image
     */
    private int $width;

    /**
     * Heigt of the image
     */
    private int $height;

    /**
     * MD5 checksum of the image data
     */
    private string $checksum;

    /**
     * MD5 checksum of the original image
     */
    private string $originalChecksum;

    /**
     * Added date
     */
    private DateTime $added;

    /**
     * Updated date
     */
    private DateTime $updated;

    /**
     * User
     */
    private string $user;

    /**
     * Image identifier
     */
    private string $imageIdentifier;

    /**
     * Flag informing us if the image has been transformed by any image transformations
     */
    private bool $hasBeenTransformed = false;

    /**
     * Track requested output quality compression
     */
    private int $outputQualityCompression;

    /**
     * Get the size of the image data in bytes
     *
     * @return ?int
     */
    public function getFilesize(): ?int
    {
        return $this->filesize ?? null;
    }

    /**
     * Set the size of the image in bytes
     *
     * @param int $size The size of the image
     * @return self
     */
    public function setFilesize(int $size): self
    {
        $this->filesize = $size;

        return $this;
    }

    /**
     * Get the mime type
     *
     * @return ?string
     */
    public function getMimeType(): ?string
    {
        return $this->mimeType ?? null;
    }

    /**
     * Set the mime type
     *
     * @param string $mimeType The mime type, for instance "image/png"
     * @return self
     */
    public function setMimeType(string $mimeType): self
    {
        if (isset(self::$mimeTypeMapping[$mimeType])) {
            // The mime type has a mapping, use that instead
            $mimeType = self::$mimeTypeMapping[$mimeType];
        }

        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get the extension
     *
     * @return ?string
     */
    public function getExtension(): ?string
    {
        return $this->extension ?? null;
    }

    /**
     * Set the extension
     *
     * @param string $extension The file extension
     * @return self
     */
    public function setExtension(string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * Get the blob
     *
     * @return ?string
     */
    public function getBlob(): ?string
    {
        return $this->blob ?? null;
    }

    /**
     * Set the blob and update filesize and checksum properties
     *
     * @param string $blob The binary data to set
     * @return self
     */
    public function setBlob(string $blob): self
    {
        $this->blob = $blob;
        $this->setFilesize(strlen($blob));
        $this->setChecksum(md5($blob));

        return $this;
    }

    /**
     * Get the metadata
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Set the metadata
     *
     * @param array $metadata An array with metadata
     * @return self
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Get the width
     *
     * @return ?int
     */
    public function getWidth(): ?int
    {
        return $this->width ?? null;
    }

    /**
     * Set the width
     *
     * @param int $width Width in pixels
     * @return Image
     */
    public function setWidth(int $width): Image
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get the height
     *
     * @return ?int
     */
    public function getHeight(): ?int
    {
        return $this->height ?? null;
    }

    /**
     * Set the height
     *
     * @param int $height Height in pixels
     * @return self
     */
    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get the added date
     *
     * @return ?DateTime
     */
    public function getAddedDate(): ?DateTime
    {
        return $this->added ?? null;
    }

    /**
     * Set the added date
     *
     * @param DateTime $added When the image was added
     * @return self
     */
    public function setAddedDate(DateTime $added): self
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get the updated date
     *
     * @return ?DateTime
     */
    public function getUpdatedDate(): ?DateTime
    {
        return $this->updated ?? null;
    }

    /**
     * Set the updated date
     *
     * @param DateTime $updated When the image was updated
     * @return self
     */
    public function setUpdatedDate(DateTime $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get the user
     *
     * @return ?string
     */
    public function getUser(): ?string
    {
        return $this->user ?? null;
    }

    /**
     * Set the user
     *
     * @param string $user The user
     * @return self
     */
    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the image identifier
     *
     * @return ?string
     */
    public function getImageIdentifier(): ?string
    {
        return $this->imageIdentifier ?? null;
    }

    /**
     * Set the image identifier
     *
     * @param string $imageIdentifier The image identifier
     * @return self
     */
    public function setImageIdentifier(string $imageIdentifier): self
    {
        $this->imageIdentifier = $imageIdentifier;

        return $this;
    }

    /**
     * Get the checksum of the current image data
     *
     * @return ?string
     */
    public function getChecksum(): ?string
    {
        return $this->checksum ?? null;
    }

    /**
     * Set the checksum
     *
     * @param string $checksum The checksum to set
     * @return self
     */
    public function setChecksum(string $checksum): self
    {
        $this->checksum = $checksum;

        return $this;
    }

    /**
     * Get the original checksum of the current image data
     *
     * @return ?string
     */
    public function getOriginalChecksum(): ?string
    {
        return $this->originalChecksum ?? null;
    }

    /**
     * Set the original checksum
     *
     * @param string $originalChecksum The original checksum to set
     * @return self
     */
    public function setOriginalChecksum(string $checksum): self
    {
        $this->originalChecksum = $checksum;

        return $this;
    }

    /**
     * Set the hasBeenTransformed flag
     *
     * @param bool $flag
     * @return self
     */
    public function setHasBeenTransformed(bool $flag): self
    {
        $this->hasBeenTransformed = $flag;

        return $this;
    }

    /**
     * Get the hasBeenTransformed flag
     *
     * @return bool
     */
    public function getHasBeenTransformed(): bool
    {
        return $this->hasBeenTransformed;
    }

    /**
     * Get the requested output quality compression or quality value
     *
     * @return ?int
     */
    public function getOutputQualityCompression(): ?int
    {
        return $this->outputQualityCompression ?? null;
    }

    /**
     * Request a specific output quality compression or quality value. The output converter for the file type must still
     * make use of the value.
     *
     * @param int $outputQualityCompression The requested compression or quality value
     * @return self
     */
    public function setOutputQualityCompression(int $outputQualityCompression): self
    {
        $this->outputQualityCompression = $outputQualityCompression;

        return $this;
    }

    /**
     * @return array{filesize:int,mimeType:string,extension:string,metadata:array<string,mixed>,width:int,height:int,addedDate:DateTime,updatedDate:DateTime,user:string,imageIdentifier:string,checksum:string,originalChecksum:string,hasBeenTransformed:bool,outputQualityCompression:int}
     */
    public function getData(): array
    {
        return [
            'filesize'                 => $this->getFilesize(),
            'mimeType'                 => $this->getMimeType(),
            'extension'                => $this->getExtension(),
            'metadata'                 => $this->getMetadata(),
            'width'                    => $this->getWidth(),
            'height'                   => $this->getHeight(),
            'addedDate'                => $this->getAddedDate(),
            'updatedDate'              => $this->getUpdatedDate(),
            'user'                     => $this->getUser(),
            'imageIdentifier'          => $this->getImageIdentifier(),
            'checksum'                 => $this->getChecksum(),
            'originalChecksum'         => $this->getOriginalChecksum(),
            'hasBeenTransformed'       => $this->getHasBeenTransformed(),
            'outputQualityCompression' => $this->getOutputQualityCompression(),
        ];
    }
}
