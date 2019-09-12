<?php
/**
 * Enable the max image size event listener, setting a max size of w1000 x h600
 */
return [
    'eventListeners' => [
        'maxImageSize' => [
            'listener' => 'Imbo\EventListener\MaxImageSize',
            'params' => [
                'width' => 1000,
                'height' => 600,
            ],
        ],
    ],
];
