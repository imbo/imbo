<?php declare(strict_types=1);
namespace Imbo\Image;

use Imagick;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;
use Imbo\Image\InputLoader\InputLoaderInterface;

/**
 * Loader manager
 *
 * This class manages loading of images by calling out to registered plugins to actually load the
 * image.
 *
 * A plugin should return an Imagick instance with the loaded image content, null if it does not
 * support the image for any reason, or throw a LoaderException if the image is determined to be
 * invalid or damaged.
 */
class InputLoaderManager
{
    /**
     * @var array Registered loaders for a given mime type
     */
    protected $loaders = [];

    /**
     * @var array Mime type mapped to the extensions the loaders support for the mime type
     */
    protected $mimeTypeToExtension = [];

    /**
     * @var Imagick The imagick instance that a loader should insert its data into
     */
    protected $imagick;

    /**
     * Set imagick instance to pass on to loaders. This is usually populated by a dedicated event listener.
     *
     * @param Imagick $imagick
     * @return self
     */
    public function setImagick(Imagick $imagick)
    {
        $this->imagick = $imagick;

        return $this;
    }

    /**
     * Add a list of input loaders to the manager.
     *
     * @param array<InputLoaderInterface|string> $loaders A list of loaders to add to the manager.
     * @return self
     */
    public function addLoaders(array $loaders)
    {
        foreach ($loaders as $loader) {
            if (is_string($loader)) {
                $loader = new $loader();
            }

            if (!$loader instanceof InputLoaderInterface) {
                $name = is_object($loader) ? get_class($loader) : (string) $loader;
                throw new InvalidArgumentException('Given loader (' . $name . ') does not implement LoaderInterface', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->registerLoader($loader);
        }

        return $this;
    }

    /**
     * Register a specific input loader for the manager to use.
     *
     * @param InputLoaderInterface $loader InputLoader to register
     * @return self
     */
    public function registerLoader(InputLoaderInterface $loader)
    {
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

            array_unshift($this->loaders[$mime], $loader);
        }

        return $this;
    }

    /**
     * Load a binary blob as an image.
     *
     * @param string $mime Mime type of the binary blob
     * @param string $blob Binary data to load / rasterize
     * @return boolean|null|Imagick Returns false if all the registered loaders was unable to load
     *                              the blob. If any of the loaders ends up with a result other than
     *                              false, the imagick instance will be returned.
     */
    public function load($mime, $blob)
    {
        if (!isset($this->loaders[$mime])) {
            return null;
        }

        foreach ($this->loaders[$mime] as $loader) {
            $result = $loader->load($this->imagick, $blob, $mime);

            // If the result is false, let the loop continue to try more loaders, if they exist. If
            // the result is anything else, return the imagick instance as this means that the
            // loader managed to load the image
            if ($result !== false) {
                // Convert images that are CMYK without an explicit profile to SRGB
                $iccProfiles = $this->imagick->getImageProfiles('icc', false);

                if (!$iccProfiles && ($this->imagick->getImageColorspace() === Imagick::COLORSPACE_CMYK)) {
                    $iccCMYK = file_get_contents(__DIR__ . '/../../data/profiles/argyllcms_cmyk.icm');
                    $this->imagick->profileImage('icc', $iccCMYK);

                    $iccSRGB = file_get_contents(__DIR__ . '/../../data/profiles/sRGB_v4_ICC_preference.icc');
                    $this->imagick->profileImage('icc', $iccSRGB);

                    $this->imagick->setImageColorSpace(Imagick::COLORSPACE_SRGB);
                }

                return $this->imagick;
            }
        }

        return false;
    }

    /**
     * Get the extension associated with a specific mime type by the loader modules.
     *
     * @param string $mimeType
     * @return string|null The extension used for the mime type or null if the mime type is unknown
     */
    public function getExtensionFromMimeType($mimeType)
    {
        return isset($this->mimeTypeToExtension[$mimeType]) ? $this->mimeTypeToExtension[$mimeType][0] : null;
    }
}
