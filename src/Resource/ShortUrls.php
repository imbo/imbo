<?php declare(strict_types=1);

namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;
use Imbo\Model\ArrayModel;
use Random\Randomizer;

use const JSON_ERROR_NONE;

class ShortUrls implements ResourceInterface
{
    private Randomizer $randomizer;

    public function __construct()
    {
        $this->randomizer = new Randomizer();
    }

    public function getAllowedMethods(): array
    {
        return ['POST', 'DELETE'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Add short URL
            'shorturls.post' => 'createShortUrl',

            // Remove short URLs for a given image
            'shorturls.delete' => 'deleteImageShortUrls',
            'image.delete' => 'deleteImageShortUrls',
        ];
    }

    /**
     * Add a short URL to the database.
     */
    public function createShortUrl(EventInterface $event): void
    {
        $request = $event->getRequest();
        $image = $request->getContent();

        if (empty($image)) {
            throw new InvalidArgumentException('Missing JSON data', Response::HTTP_BAD_REQUEST);
        } else {
            $image = json_decode($image, true);

            if (null === $image || JSON_ERROR_NONE !== json_last_error()) {
                throw new InvalidArgumentException('Invalid JSON data', Response::HTTP_BAD_REQUEST);
            }
        }

        if (!isset($image['user']) || $image['user'] !== $request->getUser()) {
            throw new InvalidArgumentException('Missing or invalid user', Response::HTTP_BAD_REQUEST);
        }

        if (!isset($image['imageIdentifier']) || $image['imageIdentifier'] !== $request->getImageIdentifier()) {
            throw new InvalidArgumentException('Missing or invalid image identifier', Response::HTTP_BAD_REQUEST);
        }

        $extension = isset($image['extension']) ? strtolower($image['extension']) : null;
        $outputConverterManager = $event->getOutputConverterManager();

        if (null !== $extension && !$outputConverterManager->supportsExtension($extension)) {
            throw new InvalidArgumentException('Extension provided is not a recognized format', Response::HTTP_BAD_REQUEST);
        }

        $queryString = isset($image['query']) ? $image['query'] : null;

        if ($queryString) {
            parse_str(urldecode(ltrim($queryString, '?')), $query);
        } else {
            $query = [];
        }

        $database = $event->getDatabase();

        if (!$database->imageExists($image['user'], $image['imageIdentifier'])) {
            throw new InvalidArgumentException('Image does not exist', Response::HTTP_NOT_FOUND);
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
            } while ($database->getShortUrlParams($shortUrlId));

            // We have an ID that does not already exist
            $database->insertShortUrl($shortUrlId, $image['user'], $image['imageIdentifier'], $extension, $query);
        }

        // Attach the header
        $model = new ArrayModel();
        $model->setData([
            'id' => $shortUrlId,
        ]);

        $event->getResponse()->setModel($model)
                             ->setStatusCode($exists ? Response::HTTP_OK : Response::HTTP_CREATED);
    }

    /**
     * Delete all short URLs for a given image.
     */
    public function deleteImageShortUrls(EventInterface $event): void
    {
        $request = $event->getRequest();
        $user = $request->getUser();
        $imageIdentifier = $request->getImageIdentifier();

        $event->getDatabase()->deleteShortUrls(
            $user,
            $imageIdentifier,
        );

        if ('shorturls.delete' === $event->getName()) {
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
     * Method for generating short URL keys.
     */
    private function getShortUrlId(int $len = 7): string
    {
        return $this->randomizer->getBytesFromString(
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
            $len,
        );
    }
}
