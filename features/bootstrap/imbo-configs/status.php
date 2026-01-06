<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imbo\Database\DatabaseInterface;
use Imbo\Http\Request\Request;
use Imbo\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\Generator\Generator;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Set a database and storage adapter that has some behaviour determined via request headers
 */
return [
    'database' => function (Request $request): DatabaseInterface {
        /** @var DatabaseInterface&MockObject */
        $adapter = (new Generator())->testDouble(
            DatabaseInterface::class,
            true,
            [],
            [],
            '',
            false,
            false,
        );
        $adapter
            ->method('getStatus')
            ->willReturn(!$request->headers->has('x-imbo-status-database-failure'));

        return $adapter;
    },

    'storage' => function (Request $request): StorageInterface {
        /** @var StorageInterface&MockObject */
        $adapter = (new Generator())->testDouble(
            StorageInterface::class,
            true,
            [],
            [],
            '',
            false,
            false,
        );
        $adapter
            ->method('getStatus')
            ->willReturn(!$request->headers->has('x-imbo-status-storage-failure'));

        return $adapter;
    },
];
