<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

/**
 * Set a database and storage adapter that has some behaviour determined via request headers
 */
return [
    'database' => function() {
        $adapter = (new PHPUnit_Framework_MockObject_Generator())->getMock(
            'Imbo\Database\MongoDB',
            ['getStatus'],
            [['databaseName' => 'imbo_testing']]
        );

        $adapter->expects(new PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount())
                ->method('getStatus')
                ->will(new PHPUnit_Framework_MockObject_Stub_Return(isset($_GET['databaseDown']) ? false : true));

        return $adapter;
    },

    'storage' => function() {
        $adapter = (new PHPUnit_Framework_MockObject_Generator())->getMock(
            'Imbo\Storage\GridFS',
            ['getStatus'],
            [['databaseName' => 'imbo_testing']]
        );

        $adapter->expects(new PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount())
                ->method('getStatus')
                ->will(new PHPUnit_Framework_MockObject_Stub_Return(isset($_GET['storageDown']) ? false : true));

        return $adapter;
    },
];
