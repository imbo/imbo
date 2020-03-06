<?php
namespace Imbo\Image;

use Imbo\EventManager\EventInterface;
use Imbo\EventListener\ListenerInterface;
use Imbo\Exception\ImageException;
use Imbo\Exception\LoaderException;
use Imbo\Exception;
use Imbo\Model\Image;
use ImagickException;
use finfo;

/**
 * Image preparation
 */
class ImagePreparation implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'images.post' => ['prepareImage' => 50],
        ];
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
        $imageBlob = $request->getContent();

        if (empty($imageBlob)) {
            $e = new ImageException('No image attached', 400);
            $e->setImboErrorCode(Exception::IMAGE_NO_IMAGE_ATTACHED);

            throw $e;
        }

        // Fetch mime using finfo
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($imageBlob);

        if (isset(Image::$mimeTypeMapping[$mime])) {
            $mime = Image::$mimeTypeMapping[$mime];
        }

        // The loader for the format determined that the image was borked. We set up the image
        // exception here since we're catching multiple exceptions below
        $invalidImageException = new ImageException('Invalid image', 415);
        $invalidImageException->setImboErrorCode(Exception::IMAGE_INVALID_IMAGE);

        // Attempt to load the image through one of the registered loaders
        try {
            $imagick = $event->getInputLoaderManager()->load($mime, $imageBlob);

            if ($imagick) {
                $size = $imagick->getImageGeometry();
            }
        } catch (ImagickException $e) {
            throw $invalidImageException;
        } catch (LoaderException $e) {
            throw $invalidImageException;
        }

        // Unsupported image type
        if (!$imagick) {
            $e = new ImageException('Unsupported image type: ' . $mime, 415);
            $e->setImboErrorCode(Exception::IMAGE_UNSUPPORTED_MIMETYPE);

            throw $e;
        }

        // Store relevant information in the image instance and attach it to the request
        $image = new Image();
        $image->setMimeType($mime)
              ->setExtension($event->getInputLoaderManager()->getExtensionFromMimetype($mime))
              ->setBlob($imageBlob)
              ->setWidth($size['width'])
              ->setHeight($size['height'])
              ->setOriginalChecksum(md5($imageBlob));

        $request->setImage($image);
    }
}
