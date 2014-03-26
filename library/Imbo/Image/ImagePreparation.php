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
    Imbo\EventListener\ListenerInterface,
    Imbo\Exception\ImageException,
    Imbo\Exception,
    Imbo\Model\Image,
    Imagick,
    ImagickException;

/**
 * Image preparation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image
 */
class ImagePreparation implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'images.post' => array('prepareImage' => 50),
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
        $imageBlob = $request->getContent();

        if (empty($imageBlob)) {
            $e = new ImageException('No image attached', 400);
            $e->setImboErrorCode(Exception::IMAGE_NO_IMAGE_ATTACHED);

            throw $e;
        }

        // Open the image with imagick to fetch the mime type
        $imagick = new Imagick();

        try {
            $imagick->readImageBlob($imageBlob);
            $mime = str_replace('x-', '', $imagick->getImageMimeType());
            $size = $imagick->getImageGeometry();
        } catch (ImagickException $e) {
            $e = new ImageException('Invalid image', 415);
            $e->setImboErrorCode(Exception::IMAGE_INVALID_IMAGE);

            throw $e;
        }

        if (!Image::supportedMimeType($mime)) {
            $e = new ImageException('Unsupported image type: ' . $mime, 415);
            $e->setImboErrorCode(Exception::IMAGE_UNSUPPORTED_MIMETYPE);

            throw $e;
        }

        // Store relevant information in the image instance and attach it to the request
        $image = new Image();
        $image->setMimeType($mime)
              ->setExtension(Image::getFileExtension($mime))
              ->setBlob($imageBlob)
              ->setWidth($size['width'])
              ->setHeight($size['height'])
              ->setOriginalChecksum(md5($imageBlob));

        $request->setImage($image);
    }
}
