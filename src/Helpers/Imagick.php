<?php declare(strict_types=1);
namespace Imbo\Helpers;

use Imagick as ImagickInternal;

/**
 * Helper class for Imagick
 */
class Imagick
{
    /**
     * Get the version number (x.y.z-p) of the ImageMagick version installed (not the extension version, but
     * the version of ImageMagick it's using). Returns null on failure.
     *
     * @return ?string
     */
    public static function getInstalledVersion(): ?string
    {
        $params = explode(' ', ImagickInternal::getVersion()['versionString']);

        if (count($params) > 2) {
            return $params[1];
        }

        return null;
    }
}
