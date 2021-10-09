<?php declare(strict_types=1);
namespace Imbo\Image;

use Imagick;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;
use Imbo\Image\OutputConverter\OutputConverterInterface;
use Imbo\Model\Image;

/**
 * Output converter manager
 *
 * This class manages converting images to the requested output format through registered plugins.
 *
 * A plugin should update the given model to reflect the new state. By calling `setBlob`, the plugin
 * can update the actual binary data returned to the client.
 */
class OutputConverterManager
{
    /**
     * @var array Registered converters by their mime type
     */
    protected $convertersByMimeType = [];

    /**
     * @var array Registered converters by the extensions they support
     */
    protected $convertersByExtension = [];

    /**
     * @var array Extensions mapped to their associated mime types
     */
    protected $extensionToMimeType = [];

    /**
     * @var array Mime types mapped to their extensions
     */
    protected $mimeTypeToExtension = [];

    /**
     * @var Imagick The imagick instance given to a converter for configuration or to get the current image
     */
    protected $imagick;

    /**
     * Add a list of converters to the available output converters.
     *
     * @param array $converters A list of objects or object names (strings) implementing `OutputConverterInterface`.
     * @return self
     */
    public function addConverters(array $converters)
    {
        foreach ($converters as $converter) {
            if (is_string($converter)) {
                $converter = new $converter();
            }

            if (!$converter instanceof OutputConverterInterface) {
                $name = is_object($converter) ? get_class($converter) : (string) $converter;
                throw new InvalidArgumentException('Given converter (' . $name . ') does not implement OutputConverterInterface', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->registerConverter($converter);
        }

        return $this;
    }

    /**
     * Register a single output converter with the manager to make it available inside Imbo.
     *
     * @param OutputConverterInterface $converter The converter to register
     * @return self
     */
    public function registerConverter(OutputConverterInterface $converter)
    {
        foreach ($converter->getSupportedMimeTypes() as $mimeType => $extensions) {
            if (!is_array($extensions)) {
                $extensions = [$extensions];
            }

            if (!isset($this->convertersByMimeType[$mimeType])) {
                $this->convertersByMimeType[$mimeType] = [];
            }

            if (!isset($this->mimeTypeToExtension[$mimeType])) {
                $this->mimeTypeToExtension[$mimeType] = $extensions[0];
            }

            $this->convertersByMimeType[$mimeType][] = $converter;

            foreach ($extensions as $extension) {
                if (!isset($this->convertersByExtension[$extension])) {
                    $this->convertersByExtension[$extension] = [];
                }

                if (!isset($this->extensionToMimeType[$extension])) {
                    $this->extensionToMimeType[$extension] = $mimeType;
                }

                $this->convertersByExtension[$extension][] = $converter;
            }
        }

        return $this;
    }

    /**
     * Ask for an image blob to be converted to its output format (i.e. configure Imagick).
     *
     * @param Image $image The image model of the image to convert
     * @param string $extension Extension we should look up the converter for
     * @param string $mime Mime type we should look up the converter from, if available
     * @return bool|null Returns true if it we were able to convert or null if we failed
     */
    public function convert(Image $image, $extension, $mime = null)
    {
        if ($this->supportsExtension($extension)) {
            foreach ($this->convertersByExtension[$extension] as $converter) {
                $result = $converter->convert($this->imagick, $image, $extension, $mime);

                if ($result !== false) {
                    $image->setMimeType($this->getMimeTypeFromExtension($extension));
                    return true;
                }
            }
        }

        if ($mime && isset($this->convertersByMimeType[$mime])) {
            foreach ($this->convertersByMimeType[$mime] as $converter) {
                $result = $converter->convert($this->imagick, $image, $extension, $mime);

                if ($result !== false) {
                    $image->setMimeType($mime);
                    return true;
                }
            }
        }

        return null;
    }

    /**
     * Get a list of extensions registered with the output converter manager
     *
     * @return array<string> A list of extensions registered with the converter manager
     */
    public function getSupportedExtensions()
    {
        return array_keys($this->convertersByExtension);
    }

    /**
     * Get a list of mime types registered with the output converter manager
     *
     * @return array<string> A list of mime types registered with the converter manager
     */
    public function getSupportedMimeTypes()
    {
        return array_keys($this->convertersByMimeType);
    }

    /**
     * Look up the mime type of a given extension.
     *
     * @param string $extension The extension to look up the mime type for
     * @return string|null
     */
    public function getMimeTypeFromExtension($extension)
    {
        return isset($this->extensionToMimeType[$extension]) ? $this->extensionToMimeType[$extension] : null;
    }

    /**
     * Look up the extension for a given mime type.
     *
     * @param string $mimeType The mime type to look up the extension for.
     * @return string|null Returns the first registered mime type for a given extension
     */
    public function getExtensionFromMimeType($mimeType)
    {
        return isset($this->mimeTypeToExtension[$mimeType]) ? $this->mimeTypeToExtension[$mimeType] : null;
    }

    /**
     * Get the mime type to extension mapping
     *
     * @return array A map of string => string entries with mime type => extension mapping
     */
    public function getMimeTypeToExtensionMap()
    {
        return $this->mimeTypeToExtension;
    }

    /**
     * Get the extension to mime type mapping
     *
     * @return array A map of string => string entries with extension => mime type mapping
     */
    public function getExtensionToMimeTypeMap()
    {
        return $this->extensionToMimeType;
    }

    /**
     * Check if we have an output converter that supports the given extension
     *
     * @param string $extension The extension to check if we support
     * @return bool Whether the extension is supported
     */
    public function supportsExtension($extension)
    {
        return !empty($this->convertersByExtension[$extension]);
    }

    /**
     * Set the imagick instance that will be configured for output or used to get raw data to perform a conversion.
     *
     * @param Imagick $imagick
     * @return self
     */
    public function setImagick(Imagick $imagick)
    {
        $this->imagick = $imagick;

        return $this;
    }
}
