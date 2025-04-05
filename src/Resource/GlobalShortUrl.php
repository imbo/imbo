<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;
use Imbo\Exception\ResourceException;
use Imbo\Http\Response\Response;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * Global short URL resource
 */
class GlobalShortUrl implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'HEAD'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Fetch an image using the short URL
            'globalshorturl.get' => 'getImage',
            'globalshorturl.head' => 'getImage',
        ];
    }

    /**
     * Fetch an image via a short URL
     *
     * @param EventInterface $event
     */
    public function getImage(EventInterface $event): void
    {
        $request = $event->getRequest();
        $route = $request->getRoute();

        $params = $event->getDatabase()->getShortUrlParams($route->get('shortUrlId'));

        if (!$params) {
            throw new ResourceException('Image not found', Response::HTTP_NOT_FOUND);
        }

        $route->set('user', $params['user']);
        $route->set('imageIdentifier', $params['imageIdentifier']);
        $route->set('extension', $params['extension']);

        $request->query = new InputBag($params['query']);
        $event->getResponse()->headers->set('X-Imbo-ShortUrl', $request->getUri());
        $event->getManager()->trigger('image.get', ['skipAccessControl' => true]);
    }
}
