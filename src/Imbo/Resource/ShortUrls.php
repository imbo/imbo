<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Resource;

use Imbo\EventManager\EventInterface,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Model\ArrayModel,
    Imbo\Model\Image as ImageModel;

/**
 * Short URL collection
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources
 */
class ShortUrls implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return ['POST', 'DELETE'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            // Add short URL
            'shorturls.post' => 'createShortUrl',

            // Remove short URLs for a given image
            'shorturls.delete' => 'deleteImageShortUrls',
            'image.delete' => 'deleteImageShortUrls',
        ];
    }

    /**
     * Add a short URL to the database
     *
     * @param EventInterface $event
     */
    public function createShortUrl(EventInterface $event) {
        $request = $event->getRequest();
        $image = $request->getContent();

        if (empty($image)) {
            throw new InvalidArgumentException('Missing JSON data', 400);
        } else {
            $image = json_decode($image, true);

            if ($image === null || json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON data', 400);
            }
        }

        if (!isset($image['user']) || $image['user'] !== $request->getUser()) {
            throw new InvalidArgumentException('Missing or invalid user', 400);
        }

        if (!isset($image['imageIdentifier']) || $image['imageIdentifier'] !== $request->getImageIdentifier()) {
            throw new InvalidArgumentException('Missing or invalid image identifier', 400);
        }

        $extension = isset($image['extension']) ? strtolower($image['extension']) : null;

        if ($extension !== null && !in_array($extension, ImageModel::$mimeTypes)) {
            throw new InvalidArgumentException('Extension provided is not a recognized format', 400);
        }

        $queryString = isset($image['query']) ? $image['query'] : null;

        if ($queryString) {
            parse_str(urldecode(ltrim($queryString, '?')), $query);
        } else {
            $query = [];
        }

        $database = $event->getDatabase();

        if (!$database->imageExists($image['user'], $image['imageIdentifier'])) {
            throw new InvalidArgumentException('Image does not exist', 404);
        }

        // See if a short URL ID already exists the for given parameters
        $exists = true;
        $shortUrlId = $database->getShortUrlId($image['user'], $image['imageIdentifier'], $extension, $query);

        if (!$shortUrlId) {
            $exists = false;

            do {
                // No short URL exists, generate an ID and insert. If the generated short URL ID
                // already exists, insert again.
                $shortUrlId = $this->getShortUrlId();
            } while($database->getShortUrlParams($shortUrlId));

            // We have an ID that does not already exist
            $database->insertShortUrl($shortUrlId, $image['user'], $image['imageIdentifier'], $extension, $query);
        }

        // Attach the header
        $model = new ArrayModel();
        $model->setData([
            'id' => $shortUrlId,
        ]);

        $event->getResponse()->setModel($model)
                             ->setStatusCode($exists ? 200 : 201);
    }

    /**
     * Delete all short URLs for a given image
     *
     * @param EventInterface $event
     */
    public function deleteImageShortUrls(EventInterface $event) {
        $request = $event->getRequest();
        $user = $request->getUser();
        $imageIdentifier = $request->getImageIdentifier();

        $event->getDatabase()->deleteShortUrls(
            $user,
            $imageIdentifier
        );

        if ($event->getName() === 'shorturls.delete') {
            // If the request is against the shorturls resource directly we need to supply a
            // response model. If this method is triggered because of an image has been deleted
            // the image resource will supply the response model
            $model = new ArrayModel();
            $model->setData([
                'imageIdentifier' => $imageIdentifier,
            ]);

            $event->getResponse()->setModel($model);
        }
    }

    /**
     * Method for generating short URL keys
     *
     * @return string
     */
    private function getShortUrlId($len = 7) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $charsLen = 62;
        $key = '';

        for ($i = 0; $i < $len; $i++) {
            $key .= $chars[mt_rand() % $charsLen];
        }

        return $key;
    }
}
