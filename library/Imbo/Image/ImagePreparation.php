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

use Imbo\Http\Request\RequestInterface,
    Imbo\EventListener\ListenerDefinition,
    Imbo\EventManager\EventInterface,
    Imbo\EventListener\ListenerInterface,
    Imbo\Exception\ImageException,
    Imbo\Exception,
    Imbo\Image\Image,
    Imbo\Container,
    Imbo\ContainerAware,
    finfo;

/**
 * Image preparation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image
 */
class ImagePreparation implements ContainerAware, ListenerInterface {
    /**
     * Service container
     *
     * @var Container
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container) {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            new ListenerDefinition('image.put', array($this, 'prepareImage'), 50),
        );
    }

    /**
     * Prepare an image
     *
     * This method should prepare an image object from php://input. The method must also figure out
     * the width, height, mime type and extension of the image.
     *
     * @param EventInterface $event The current event
     * @throws ImageException
     */
    public function prepareImage(EventInterface $event) {
        $request = $event->getRequest();

        // Fetch image data from input
        $imageBlob = $request->getRawData();

        if (empty($imageBlob)) {
            $e = new ImageException('No image attached', 400);
            $e->setImboErrorCode(Exception::IMAGE_NO_IMAGE_ATTACHED);

            throw $e;
        }

        // Calculate hash
        $actualHash = md5($imageBlob);

        // Get image identifier from request
        $imageIdentifier = $request->getImageIdentifier();

        if ($actualHash !== $imageIdentifier) {
            $e = new ImageException('Hash mismatch', 400);
            $e->setImboErrorCode(Exception::IMAGE_HASH_MISMATCH);

            throw $e;
        }

        // Use the file info extension to fetch the mime type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($imageBlob);

        if (!Image::supportedMimeType($mime)) {
            $e = new ImageException('Unsupported image type: ' . $mime, 415);
            $e->setImboErrorCode(Exception::IMAGE_UNSUPPORTED_MIMETYPE);

            throw $e;
        }

        $extension = Image::getFileExtension($mime);

        if (function_exists('getimagesizefromstring')) {
            // Available since php-5.4.0
            $size = getimagesizefromstring($imageBlob);
        } else {
            $tmpFile = tempnam(sys_get_temp_dir(), 'Imbo_uploaded_image');
            file_put_contents($tmpFile, $imageBlob);
            $size = getimagesize($tmpFile);
            unlink($tmpFile);
        }

        if (!$size) {
            $e = new ImageException('Broken image', 415);
            $e->setImboErrorCode(Exception::IMAGE_BROKEN_IMAGE);

            throw $e;
        }

        // Store relevant information in the image instance and attach it to the request
        $image = $this->container->get('image');
        $image->setMimeType($mime)
              ->setExtension($extension)
              ->setBlob($imageBlob)
              ->setWidth($size[0])
              ->setHeight($size[1]);

        $request->setImage($image);
    }
}
