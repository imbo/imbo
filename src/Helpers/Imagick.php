<?php
namespace Imbo\Helpers;

/**
 * Helper class for Imagick
 *
 * @package Core\Helpers
 */
class Imagick {
    /**
     * Get the version number (x.y.z-p) of the ImageMagick version installed (not the extension version, but
     * the version of ImageMagick it's using). Returns null on failure.
     *
     * @return string|null
     */
    public static function getInstalledVersion() {
        $params = explode(' ', \Imagick::getVersion()['versionString']);

        if (count($params) > 2) {
            return $params[1];
        }

        return null;
    }
}