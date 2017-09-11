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

use Imbo\EventManager\EventInterface,
    Imbo\Image\Loader\LoaderInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Image\OutputConverter\OutputConverterInterface;

/**
 * Output converter manager. This class manages converting images to the requested output format
 * through registered plugins.
 *
 * A plugin should update the given model to reflect the new state. By calling `setBlob`, the plugin
 * can update the actual binary data returned to the client.
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Image
 */
class OutputConverterManager {
    protected $convertersByMimetype = [];
    protected $convertersByExtension = [];
    protected $extensionToMimetype = [];
    protected $mimetypeToExtension = [];
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
        foreach ($converter->getSupportedFormatsWithCallbacks() as $formatEntry) {
            if (!is_array($formatEntry) || !isset($formatEntry['mime'], $formatEntry['extension'], $formatEntry['callback']))
            {
                throw new InvalidArgumentException('Output converted returned invalid converter definition (\'mime\', \'extension\', and \'callback\') ' .
                                                   'must be defined for each supported format. Got \'' . json_encode($formatEntry) . '\'', 500);
            }

            $mimes = $formatEntry['mime'];
            $extensions = $formatEntry['extension'];

            // Since a converter can register the same callback for multiple mime types or extensions, we iterate over the values here.
            if (!is_array($mimes)) {
                $mimes = [$mimes];
            }

            if (!is_array($extensions)) {
                $extensions = [$extensions];
            }

            foreach ($mimes as $mime) {
                if (!isset($this->convertersByMimetype[$mime])) {
                    $this->convertersByMimetype[$mime] = [];
                }

                if (!isset($this->mimetypeToExtension[$mime])) {
                    $this->mimetypeToExtension[$mime] = $extensions[0];
                }

                $this->convertersByMimetype[$mime][] = $formatEntry['callback'];
            }

            foreach ($extensions as $extension) {
                if (!isset($this->convertersByExtension[$extension])) {
                    $this->convertersByExtension[$extension] = [];
                }

                if (!isset($this->extensionToMimetype[$extension])) {
                    $this->extensionToMimetype[$extension] = $mimes[0];
                }

                $this->convertersByExtension[$extension][] = $formatEntry['callback'];
            }
        }
    }

    public function convert($image, $extension, $mime = null) {
        if ($this->supportsExtension($extension)) {
            foreach ($this->convertersByExtension[$extension] as $converter) {
                $result = $converter($this->imagick, $image, $extension, $mime);

                if ($result !== false) {
                    $image->setMimeType($this->getMimetypeFromExtension($extension));
                    return true;
                }
            }
        }

        if ($mime && isset($this->convertersByMimetype[$mime])) {
            foreach ($this->convertersByMimetype[$mime] as $converter) {
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

    public function getSupportedMimetypes() {
        return array_keys($this->convertersByMimetype);
    }

    public function getMimetypeFromExtension($extension) {
        return isset($this->extensionToMimetype[$extension]) ? $this->extensionToMimetype[$extension] : null;
    }

    public function getExtensionFromMimetype($mimetype) {
        return isset($this->mimetypeToExtension[$mimetype]) ? $this->mimetypeToExtension[$mimetype] : null;
    }

    public function getMimetypeToExtensionMap() {
        return $this->mimetypeToExtension;
    }

    public function getExtensionToMimetypeMap() {
        return $this->extensionToMimetype;
    }

    public function supportsExtension($extension) {
        return !empty($this->convertersByExtension[$extension]);
    }

    public function setImagick(\Imagick $imagick) {
        $this->imagick = $imagick;
    }
}
