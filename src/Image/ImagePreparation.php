<?php declare(strict_types=1);
namespace Imbo\Image;

use finfo;
use ImagickException;
use Imbo\EventListener\ListenerInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception;
use Imbo\Exception\ImageException;
use Imbo\Exception\LoaderException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;

/**
 * Image preparation
 */
class ImagePreparation implements ListenerInterface
{
    public static function getSubscribedEvents(): array
    {
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
    public function prepareImage(EventInterface $event): void
    {
        $request = $event->getRequest();

        // Fetch image data from input
        $imageBlob = $request->getContent();

        if (empty($imageBlob)) {
            $e = new ImageException('No image attached', Response::HTTP_BAD_REQUEST);
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
        $invalidImageException = new ImageException('Invalid image', Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        $invalidImageException->setImboErrorCode(Exception::IMAGE_INVALID_IMAGE);

        // Attempt to load the image through one of the registered loaders
        try {
            $imagick = $event->getInputLoaderManager()->load($mime, $imageBlob);

            if ($imagick) {
                $size = $imagick->getImageGeometry();
                if (0 === ($size['width'] * $size['height'])) {
                    throw $invalidImageException;
                }
            }
        } catch (ImagickException|LoaderException $e) {
            throw $invalidImageException;
        }

        // Unsupported image type
        if (!$imagick) {
            $e = new ImageException('Unsupported image type: ' . $mime, Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
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
