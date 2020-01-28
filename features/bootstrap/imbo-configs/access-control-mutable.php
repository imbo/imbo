<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imbo\Auth\AccessControl\Adapter\MongoDB;

return [
    'accessControl' => function() {
        return new MongoDB([
            'databaseName' => 'imbo_testing',
        ]);
    },
];
