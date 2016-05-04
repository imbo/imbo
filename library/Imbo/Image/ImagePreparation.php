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
    Imbo\Image\Identifier\Generator\GeneratorInterface,
    Imbo\Exception\ImageException,
    Imbo\Exception,
    Imbo\Model\Image,
    Imagick,
    ImagickException,
    finfo;

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

        if (!Image::supportedMimeType($mime)) {
            $e = new ImageException('Unsupported image type: ' . $mime, 415);
            $e->setImboErrorCode(Exception::IMAGE_UNSUPPORTED_MIMETYPE);

            throw $e;
        }

        // Open the image with imagick to make sure it's valid and to fetch dimensions
        $imagick = new Imagick();

        try {
            $imagick->readImageBlob($imageBlob);
            $size = $imagick->getImageGeometry();
        } catch (ImagickException $e) {
            $e = new ImageException('Invalid image', 415);
            $e->setImboErrorCode(Exception::IMAGE_INVALID_IMAGE);

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

        $imageIdentifier = $this->generateImageIdentifier($event, $image);
        $image->setImageIdentifier($imageIdentifier);

        $request->setImage($image);
    }

    /**
     * Using the configured image identifier generator, attempt to generate a unique image
     * identifier for the given image until we either have found a unique ID or we hit the maximum
     * allowed attempts.
     *
     * @param EventInterface $event The current event
     * @param Image $image The event to generate the image identifier for
     * @return string
     * @throws ImageException
     */
    private function generateImageIdentifier(EventInterface $event, Image $image) {
        $database = $event->getDatabase();
        $config = $event->getConfig();
        $user = $event->getRequest()->getUser();
        $imageIdentifierGenerator = $config['imageIdentifierGenerator'];

        if (is_callable($imageIdentifierGenerator) &&
            !($imageIdentifierGenerator instanceof GeneratorInterface)) {
            $imageIdentifierGenerator = $imageIdentifierGenerator();
        }

        if ($imageIdentifierGenerator->isDeterministic()) {
            return $imageIdentifierGenerator->generate($image);
        }

        // Continue generating image identifiers until we get one that does not already exist
        $maxAttempts = 100;
        $attempts = 0;
        do {
            $imageIdentifier = $imageIdentifierGenerator->generate($image);
            $attempts++;
        } while ($attempts < $maxAttempts && $database->imageExists($user, $imageIdentifier));

        // Did we reach our max attempts limit?
        if ($attempts === $maxAttempts) {
            $e = new ImageException('Failed to generate unique image identifier', 503);
            $e->setImboErrorCode(Exception::IMAGE_IDENTIFIER_GENERATION_FAILED);

            // Tell the client it's OK to retry later
            $event->getResponse()->headers->set('Retry-After', 1);

            throw $e;
        }

        return $imageIdentifier;
    }
}
