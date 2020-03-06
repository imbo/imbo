<?php
namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;
use Imbo\Model;

class Index implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return ['GET', 'HEAD'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
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
    public function get(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $redirectUrl = $event->getConfig()['indexRedirect'];

        if ($redirectUrl) {
            $response->setStatusCode(307);
            $response->headers->set('Location', $redirectUrl);
            return;
        }

        $response->setStatusCode(200, 'Hell Yeah');

        $baseUrl = $request->getSchemeAndHttpHost() . $request->getBaseUrl();

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
