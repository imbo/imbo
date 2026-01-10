<?php declare(strict_types=1);

namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;
use Imbo\Model;

/**
 * Metadata resource.
 */
class Metadata implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'POST', 'PUT', 'DELETE', 'HEAD'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'metadata.head' => 'get',
            'metadata.get' => 'get',
            'metadata.post' => [
                'post',
                'validateMetadata' => 10,
            ],
            'metadata.put' => [
                'put',
                'validateMetadata' => 10,
            ],
            'metadata.delete' => 'delete',
        ];
    }

    /**
     * Handle DELETE requests.
     *
     * @param EventInterface $event The current event
     */
    public function delete(EventInterface $event): void
    {
        $event->getManager()->trigger('db.metadata.delete');
        $event->getResponse()->setModel(new Model\Metadata());
    }

    /**
     * Handle PUT requests.
     *
     * @param EventInterface $event The current event
     */
    public function put(EventInterface $event): void
    {
        $request = $event->getRequest();
        $metadata = json_decode($request->getContent(), true);

        $event->getManager()
            ->trigger('db.metadata.delete')
            ->trigger('db.metadata.update', [
                'metadata' => $metadata,
            ]);

        $model = new Model\Metadata();
        $model->setData($metadata);

        $event->getResponse()->setModel($model);
    }

    /**
     * Handle POST requests.
     *
     * @param EventInterface $event The current event
     */
    public function post(EventInterface $event): void
    {
        $request = $event->getRequest();

        $event->getManager()->trigger('db.metadata.update', [
            'metadata' => json_decode($request->getContent(), true),
        ]);

        $model = new Model\Metadata();
        $model->setData($event->getDatabase()->getMetadata($request->getUser(), $request->getImageIdentifier()));

        $event->getResponse()->setModel($model);
    }

    /**
     * Handle GET requests.
     *
     * @param EventInterface $event The current event
     */
    public function get(EventInterface $event): void
    {
        $event->getManager()->trigger('db.metadata.load');
    }

    /**
     * Validate metadata found in the request body.
     *
     * @param EventInterface $event The event instance
     *
     * @throws InvalidArgumentException
     */
    public function validateMetadata(EventInterface $event): void
    {
        $request = $event->getRequest();
        $metadata = $request->getContent();

        if (empty($metadata)) {
            throw new InvalidArgumentException('Missing JSON data', Response::HTTP_BAD_REQUEST);
        } else {
            $metadata = json_decode($metadata, true);

            if (null === $metadata) {
                throw new InvalidArgumentException('Invalid JSON data', Response::HTTP_BAD_REQUEST);
            }

            foreach (array_keys($metadata) as $key) {
                if (!str_contains($key, '.')) {
                    continue;
                }

                throw new InvalidArgumentException('Invalid metadata. Dot characters (\'.\') are not allowed in metadata keys', Response::HTTP_BAD_REQUEST);
            }
        }
    }
}
