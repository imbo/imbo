<?php declare(strict_types=1);
/**
 * Custom HTTP cache header configuration
 */
return [
    'httpCacheHeaders' => [
        'maxAge' => 15,
        'mustRevalidate' => false,
        'public' => false,
    ],
];
