<?php declare(strict_types=1);
/**
 * Enable the auto rotate event listener
 */
return [
    'eventListeners' => [
        'autoRotateListener' => 'Imbo\EventListener\AutoRotateImage',
    ],
];
