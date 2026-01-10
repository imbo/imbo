<?php declare(strict_types=1);

namespace Imbo\Behat;

use Imbo\EventListener\AutoRotateImage;

return [
    'eventListeners' => [
        'autoRotateListener' => AutoRotateImage::class,
    ],
];
