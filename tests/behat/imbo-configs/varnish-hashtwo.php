<?php
/**
 * Enable the Varnish hashtwo event listener
 */
return [
    'eventListeners' => [
        'varnishHashTwo' => 'Imbo\EventListener\VarnishHashTwo',
        'customVarnishHashTwo' => [
            'listener' => 'Imbo\EventListener\VarnishHashTwo',
            'params' => [
                'headerName' => 'X-Imbo-HashTwo',
            ],
        ],
    ],
];
