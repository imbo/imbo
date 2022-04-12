<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imbo\Database\DatabaseInterface;
use Imbo\Http\Request\Request;
use Imbo\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\Generator;

$generator = new Generator();

/**
 * Set a database and storage adapter that has some behaviour determined via request headers
 */
return [
    'database' => function (Request $request) use ($generator): DatabaseInterface {
        $adapter = $generator->getMock(DatabaseInterface::class);
        $adapter
            ->method('getStatus')
            ->willReturn(!$request->headers->has('x-imbo-status-database-failure'));

        return $adapter;
    },

    'storage' => function (Request $request) use ($generator): StorageInterface {
        $adapter = $generator->getMock(StorageInterface::class);
        $adapter
            ->method('getStatus')
            ->willReturn(!$request->headers->has('x-imbo-status-storage-failure'));

        return $adapter;
    },
];
