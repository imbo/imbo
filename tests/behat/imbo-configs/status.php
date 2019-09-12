<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;

use PHPUnit\Framework\MockObject\Generator;
use PHPUnit\Framework\MockObject\Matcher\AnyInvokedCount as Any;
use PHPUnit\Framework\MockObject\Stub\ReturnStub as ReturnValue;

/**
 * Set a database and storage adapter that has some behaviour determined via request headers
 */
return [
    'database' => function(Request $request, Response $response) {
        $adapter = (new Generator())->getMock('Imbo\Database\DatabaseInterface');
        $adapter
            ->expects(new Any())
            ->method('getStatus')
            ->will(new ReturnValue(!isset($_SERVER['HTTP_X_IMBO_STATUS_DATABASE_FAILURE'])));

        return $adapter;
    },

    'storage' => function(Request $request, Response $response) {
        $adapter = (new Generator())->getMock('Imbo\Storage\StorageInterface');
        $adapter
            ->expects(new Any())
            ->method('getStatus')
            ->will(new ReturnValue(!isset($_SERVER['HTTP_X_IMBO_STATUS_STORAGE_FAILURE'])));

        return $adapter;
    },
];
