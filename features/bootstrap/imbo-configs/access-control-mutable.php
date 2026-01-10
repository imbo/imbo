<?php declare(strict_types=1);

namespace Imbo\Behat;

use Imbo\Auth\AccessControl\Adapter\MongoDB;

return [
    'accessControl' => new MongoDB(
        'imbo_testing',
        'mongodb://localhost:27017',
        [
            'username' => 'admin',
            'password' => 'password',
        ],
    ),
];
