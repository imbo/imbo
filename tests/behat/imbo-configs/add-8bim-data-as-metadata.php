<?php
/**
 * Enable the 8BIM metadata listener
 */
return [
    'eventListeners' => [
        '8BIMMetadataListener' => [
            'listener' => 'Imbo\EventListener\EightbimMetadata',
        ]
    ],
];
