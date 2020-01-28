<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imbo\EventListener\MaxImageSize;

return [
    'eventListeners' => [
        'maxImageSize' => [
            'listener' => MaxImageSize::class,
            'params' => [
                'width' => 1000,
                'height' => 600,
            ],
        ],
    ],
];
