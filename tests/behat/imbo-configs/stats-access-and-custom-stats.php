<?php declare(strict_types=1);
use Imbo\EventListener\StatsAccess;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\EventManager\EventInterface;

/**
 * Enable the stats access event listener, using a HTTP request header to set the allowed range of
 * IPs, and optionally a custom IP for the client which will override $_SERVER['REMOTE_ADDR'].
 *
 * Also add some custom stats that will be included in the response from the stats endpoint
 */

// Rewrite the client IP when a custom header exists
if (isset($_SERVER['HTTP_X_CLIENT_IP'])) {
    // Overwrite the default client IP
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_CLIENT_IP'];
}

return [
    'eventListeners' => [
        'statsAccess' => function(Request $request, Response $response) {
            $statsAllow = [];

            if (!empty($_SERVER['HTTP_X_IMBO_STATS_ALLOWED_BY'])) {
                $statsAllow = explode(',', $_SERVER['HTTP_X_IMBO_STATS_ALLOWED_BY']);
            }

            return new StatsAccess([
                'allow' => $statsAllow,
            ]);
        },
        'customStats' => [
            'events' => ['stats.get'],
            'callback' => function(EventInterface $event) {
                // Fetch the model from the response
                $model = $event->getResponse()->getModel();

                // Set some values
                $model['someValue'] = 123;
                $model['someOtherValue'] = [
                    'foo' => 'bar',
                ];
                $model['someList'] = [1, 2, 3];
            }
        ]
    ],
];
