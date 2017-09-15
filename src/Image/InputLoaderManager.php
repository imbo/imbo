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

use Imbo\Image\InputLoader\InputLoaderInterface,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Exception\LoaderException,
    \Imagick;

/**
 * Loader manager
 *
 * This class manages loading of images by calling out to registered plugins to actually load the
 * image.
 *
 * A plugin should return an Imagick instance with the loaded image content, null if it does not
 * support the image for any reason, or throw a LoaderException if the image is determined to be
 * invalid or damaged.
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Image
 */
class InputLoaderManager {
    protected $loaders = [];
    protected $mimeTypeToExtension = [];
    protected $imagick;

    public function setImagick(\Imagick $imagick) {
        $this->imagick = $imagick;
    }

    public function addLoaders(array $loaders) {
        foreach ($loaders as $loader) {
            if (is_string($loader)) {
                $loader = new $loader();
            }

            if (!$loader instanceof InputLoaderInterface) {
                $name = is_object($loader) ? get_class($loader) : (string) $loader;
                throw new InvalidArgumentException('Given loader (' . $name . ') does not implement LoaderInterface', 500);
            }

            $this->registerLoader($loader);
        }
    }

    public function registerLoader(InputLoaderInterface $loader) {
        foreach ($loader->getSupportedMimeTypes() as $mime => $extensions) {
            if (!isset($this->loaders[$mime])) {
                $this->loaders[$mime] = [];
            }

            if (!isset($this->mimeTypeToExtension[$mime])) {
                $this->mimeTypeToExtension[$mime] = [];
            }

            if (!is_array($extensions)) {
                $extensions = [$extensions];
            }

            foreach ($extensions as $extension) {
                $this->mimeTypeToExtension[$mime][] = $extension;
            }

            $this->loaders[$mime][] = $loader;
        }
    }

    public function load($mime, $blob) {
        if (!isset($this->loaders[$mime])) {
            return null;
        }

        foreach ($this->loaders[$mime] as $loader) {
            $state = $loader->load($this->imagick, $blob, $mime);

            if ($state !== false) {
                return $state;
            }
        }

        return false;
    }

    public function getExtensionFromMimeType($mimeType) {
        return isset($this->mimeTypeToExtension[$mimeType]) ? $this->mimeTypeToExtension[$mimeType][0] : null;
    }
}
