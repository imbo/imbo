<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imbo\EventListener\EightbimMetadata;

return [
    'eventListeners' => [
        '8BIMMetadataListener' => [
            'listener' => EightbimMetadata::class,
        ],
    ],
];
