<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;
use Imbo\Exception\DuplicateImageIdentifierException;
use Imbo\Exception\ImageException;
use Imbo\Http\Response\Response;
use Imbo\Image\Identifier\Generator\GeneratorInterface;
use Imbo\Model;

/**
 * Images resource
 *
 * This resource will let users fetch images based on queries. The following query parameters can
 * be used:
 *
 * page     => Page number. Defaults to 1
 * limit    => Limit to a number of images pr. page. Defaults to 20
 * metadata => Whether or not to include metadata pr. image. Set to 1 to enable
 * query    => urlencoded json data to use in the query
 * from     => Unix timestamp to fetch from
 * to       => Unit timestamp to fetch to
 */
class Images implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'HEAD', 'POST'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'images.get' => 'getImages',
            'images.head' => 'getImages',
            'images.post' => 'addImage',
        ];
    }

    /**
     * Handle GET and HEAD requests
     *
     * @param EventInterface $event The current event
     */
    public function getImages(EventInterface $event): void
    {
        $event->getManager()->trigger('db.images.load');
    }

    /**
     * Handle POST requests
     *
     * @param EventInterface $event
     */
    public function addImage(EventInterface $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $image = $request->getImage();

        // attempt to store the image in the underlying database
        $maxAttempts = 100;
        $config = $event->getConfig();

        // retrieve and instantiate if necessary the image identifier generator
        $imageIdentifierGenerator = $config['imageIdentifierGenerator'];

        if (is_callable($imageIdentifierGenerator) &&
            !($imageIdentifierGenerator instanceof GeneratorInterface)) {
            $imageIdentifierGenerator = $imageIdentifierGenerator();
        }

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                $image->setImageIdentifier($imageIdentifierGenerator->generate($image));
                $event->getManager()->trigger('db.image.insert', ['updateIfDuplicate' => $imageIdentifierGenerator->isDeterministic()]);
                break;
            } catch (DuplicateImageIdentifierException $exception) {
                // the image identifier already exists - or was created before we inserted
                // so we retry the event to get the last submitted image
                if ($imageIdentifierGenerator->isDeterministic()) {
                    $event->getManager()->trigger('db.image.insert', ['updateIfDuplicate' => $imageIdentifierGenerator->isDeterministic()]);
                }

                continue;
            }
        }

        // Image Identifier generation failed - throw exception to get us out of this state
        if ($attempt === $maxAttempts) {
            $e = new ImageException('Failed to generate unique image identifier', Response::HTTP_SERVICE_UNAVAILABLE);
            $e->setImboErrorCode(ImageException::IMAGE_IDENTIFIER_GENERATION_FAILED);

            // Tell the client it's OK to retry later
            $event->getResponse()->headers->set('Retry-After', 1);
            throw $e;
        }

        $event->getManager()->trigger('storage.image.insert');

        $model = new Model\ArrayModel();
        $model->setData([
            'imageIdentifier' => $image->getImageIdentifier(),
            'width' => $image->getWidth(),
            'height' => $image->getHeight(),
            'extension' => $image->getExtension(),
        ]);

        $response->setModel($model);
    }
}
