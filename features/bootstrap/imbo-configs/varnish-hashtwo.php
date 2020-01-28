<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imbo\EventListener\VarnishHashTwo;

return [
    'eventListeners' => [
        'varnishHashTwo' => VarnishHashTwo::class,
        'customVarnishHashTwo' => [
            'listener' => VarnishHashTwo::class,
            'params' => [
                'headerName' => 'X-Imbo-HashTwo',
            ],
        ],
    ],
];
