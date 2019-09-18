<?php declare(strict_types=1);
use Imbo\Auth\AccessControl\Adapter\MongoDB as MongoAclAdapter;

return [
    'accessControl' => function() {
        return new MongoAclAdapter([
            'databaseName' => 'imbo_testing',
        ]);
    }
];
