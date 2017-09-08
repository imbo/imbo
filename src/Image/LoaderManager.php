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

/**
 * Loader manager. This class manages loading of images by calling out to
 * registered plugins to actually load the image.
 *
 * A plugin should return an Imagick instance with the loaded image content,
 * null if it does not support the image for any reason, or throw a LoaderException
 * if the image is determined to be invalid or damaged.
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Image
 */
class LoaderManager {
    protected $loaders = [];

    public function addLoaders(array $loaders) {
        foreach ($loaders as $loader) {
            if (!$loader instanceof LoaderInterface) {
                $name = is_object($loader) ? get_class($loader) : (string) $loader;
                throw new InvalidArgumentException('Given loader (' . $name . ') does not implement LoaderInterface', 500);
            }

            $this->registerLoader($loader);
        }
    }

    public function registerLoader(LoaderInterface $loader) {
        foreach ($loader->getMimeTypeCallbacks() as $mime => $callback) {
            if (!isset($this->loaders[$mime])) {
                $this->loaders[$mime] = [];
            }

            $this->loaders[$mime][] = $callback;
        }
    }

    public function load($mime, $blob) {
        if (!isset($this->loaders[$mime])) {
            return null;
        }

        foreach ($this->loaders[$mime] as $callback) {
            $imagick = $callback($blob);

            if ($imagick) {
                return $imagick;
            }
        }

        return null;
    }
}
