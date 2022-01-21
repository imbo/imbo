<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Response\Response;
use Imbo\Model;

class Index implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'HEAD'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'index.get' => 'get',
            'index.head' => 'get',
        ];
    }

    /**
     * Handle GET requests
     *
     * @param EventInterface $event The current event
     */
    public function get(EventInterface $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $redirectUrl = $event->getConfig()['indexRedirect'];

        if ($redirectUrl) {
            $response->setStatusCode(Response::HTTP_TEMPORARY_REDIRECT);
            $response->headers->set('Location', $redirectUrl);
            return;
        }

        $response->setStatusCode(Response::HTTP_OK, 'Hell Yeah');

        $model = new Model\ArrayModel();
        $model->setData([
            'site' => 'http://imbo.io',
            'source' => 'https://github.com/imbo/imbo',
            'issues' => 'https://github.com/imbo/imbo/issues',
            'docs' => 'http://docs.imbo.io',
        ]);

        $response->setModel($model);

        // Prevent caching
        $response->setMaxAge(0)
                 ->setPrivate();
        $response->headers->addCacheControlDirective('no-store');
    }
}
