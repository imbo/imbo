<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

// Rewrite the client IP when a custom header exists
if (isset($_SERVER['HTTP_X_CLIENT_IP'])) {
    // Overwrite the default client IP
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_CLIENT_IP'];
}

/**
 * Enable the stats access event listener, using query parameter values to set the allowed range
 * of IP's, and optionally a custom IP for the client.
 *
 * Also add some custom stats that will be included in the response from the stats endpoint
 */
return [
    'eventListeners' => [
        'statsAccess' => function() {
            $statsAllow = [];

            if (!empty($_GET['statsAllow'])) {
                // Set the range
                $statsAllow = explode(',', $_GET['statsAllow']);
            }

            return new Imbo\EventListener\StatsAccess([
                'allow' => $statsAllow,
            ]);
        },
        'customStats' => [
            'events' => ['stats.get'],
            'callback' => function($event) {
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
