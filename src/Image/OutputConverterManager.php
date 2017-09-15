<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image;

use Imbo\Exception\InvalidArgumentException,
    Imbo\Image\OutputConverter\OutputConverterInterface,
    \Imagick;

/**
 * Output converter manager
 *
 * This class manages converting images to the requested output format through registered plugins.
 *
 * A plugin should update the given model to reflect the new state. By calling `setBlob`, the plugin
 * can update the actual binary data returned to the client.
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Image
 */
class OutputConverterManager {
    protected $convertersByMimeType = [];
    protected $convertersByExtension = [];
    protected $extensionToMimeType = [];
    protected $mimeTypeToExtension = [];
    protected $imagick;

    public function addConverters(array $converters) {
        foreach ($converters as $converter) {
            if (is_string($converter)) {
                $converter = new $converter();
            }

            if (!$converter instanceof OutputConverterInterface) {
                $name = is_object($converter) ? get_class($converter) : (string) $converter;
                throw new InvalidArgumentException('Given converter (' . $name . ') does not implement OutputConverterInterface', 500);
            }

            $this->registerConverter($converter);
        }
    }

    public function registerConverter(OutputConverterInterface $converter) {
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
    }

    public function convert($image, $extension, $mime = null) {
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
                $result = $converter($this->imagick, $image, $extension, $mime);

                if ($result !== false) {
                    $image->setMimeType($mime);
                    return true;
                }
            }
        }

        return null;
    }

    public function getSupportedExtensions() {
        return array_keys($this->convertersByExtension);
    }

    public function getSupportedMimeTypes() {
        return array_keys($this->convertersByMimeType);
    }

    public function getMimeTypeFromExtension($extension) {
        return isset($this->extensionToMimeType[$extension]) ? $this->extensionToMimeType[$extension] : null;
    }

    public function getExtensionFromMimeType($mimetype) {
        return isset($this->mimeTypeToExtension[$mimetype]) ? $this->mimeTypeToExtension[$mimetype] : null;
    }

    public function getMimeTypeToExtensionMap() {
        return $this->mimeTypeToExtension;
    }

    public function getExtensionToMimeTypeMap() {
        return $this->extensionToMimeType;
    }

    public function supportsExtension($extension) {
        return !empty($this->convertersByExtension[$extension]);
    }

    public function setImagick(Imagick $imagick) {
        $this->imagick = $imagick;
    }
}
