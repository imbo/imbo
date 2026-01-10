<?php declare(strict_types=1);

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Symfony\Component\HttpFoundation\Request;

class HttpCache implements ListenerInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'response.send' => ['setHeaders' => 1],
        ];
    }

    public function setHeaders(EventInterface $event): void
    {
        $method = $event->getRequest()->getMethod();

        if (Request::METHOD_GET !== $method && Request::METHOD_HEAD !== $method) {
            return;
        }

        $response = $event->getResponse();

        if ('public' !== $response->headers->get('Cache-Control')) {
            return;
        }

        $config = $event->getConfig()['httpCacheHeaders'];

        if (isset($config['maxAge'])) {
            $response->setMaxAge((int) $config['maxAge']);
        }

        if (isset($config['sharedMaxAge'])) {
            $response->setSharedMaxAge($config['sharedMaxAge']);
        }

        if (isset($config['public']) && $config['public']) {
            $response->setPublic();
        } elseif (isset($config['public'])) {
            $response->setPrivate();
        }

        if (isset($config['mustRevalidate']) && $config['mustRevalidate']) {
            $response->headers->addCacheControlDirective('must-revalidate');
        }
    }
}
