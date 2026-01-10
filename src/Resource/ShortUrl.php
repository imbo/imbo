<?php declare(strict_types=1);

namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;
use Imbo\Exception\ResourceException;
use Imbo\Http\Response\Response;
use Imbo\Model\ArrayModel;

class ShortUrl implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return [
            'HEAD',
            'GET',
            'DELETE',
        ];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'shorturl.head' => 'getShortUrl',
            'shorturl.get' => 'getShortUrl',
            'shorturl.delete' => 'deleteShortUrl',
        ];
    }

    public function getShortUrl(EventInterface $event): void
    {
        $database = $event->getDatabase();
        $request = $event->getRequest();
        $user = $request->getUser();
        $imageIdentifier = $request->getImageIdentifier();
        $shortUrlId = $request->getRoute()->get('shortUrlId');

        if (!$params = $database->getShortUrlParams($shortUrlId)) {
            throw new ResourceException('ShortURL not found', Response::HTTP_NOT_FOUND);
        }

        if ($params['user'] !== $user || $params['imageIdentifier'] !== $imageIdentifier) {
            throw new ResourceException('ShortURL not found', Response::HTTP_NOT_FOUND);
        }

        $model = new ArrayModel();
        $model->setData($params);
        $event->getResponse()->setModel($model);
    }

    public function deleteShortUrl(EventInterface $event): void
    {
        $database = $event->getDatabase();
        $request = $event->getRequest();
        $user = $request->getUser();
        $imageIdentifier = $request->getImageIdentifier();
        $shortUrlId = $request->getRoute()->get('shortUrlId');

        if (!$params = $database->getShortUrlParams($shortUrlId)) {
            throw new ResourceException('ShortURL not found', Response::HTTP_NOT_FOUND);
        }

        if ($params['user'] !== $user || $params['imageIdentifier'] !== $imageIdentifier) {
            throw new ResourceException('ShortURL not found', Response::HTTP_NOT_FOUND);
        }

        $database->deleteShortUrls(
            $user,
            $imageIdentifier,
            $shortUrlId,
        );

        $model = new ArrayModel();
        $model->setData([
            'id' => $shortUrlId,
        ]);

        $event->getResponse()->setModel($model);
    }
}
