.. _configuration:

Configuration
=============

Imbo ships with a default configuration file that will be automatically loaded. You will have to create one or more configuration files of your own that will be automatically merged with the default configuration by Imbo. The location of these files depends on the :ref:`installation method <installation>` you choose. You should never have to edit the default configuration file provided by Imbo.

The configuration file(s) you need to create should simply return arrays with configuration data. All available configuration options are covered in this chapter.

.. contents::
    :local:
    :depth: 1

.. _configuration-with-callables:

Using callables in configuration
--------------------------------

Imbo supports providing callables for certain configuration options. In Imbo 2 these are the :ref:`accessControl <access-control-configuration>`, :ref:`database <database-configuration>`, :ref:`eventListeners[name] <event-listeners-configuration>`, :ref:`resource <resource-configuration>` and :ref:`storage <storage-configuration>` options, while Imbo 3 adds callable support for :ref:`transformationPresets <transformation-presets-configuration>`. The callable receives two arguments (`$request` and `$response`) which map to the active request and response objects.

By using a callable you can extend Imbo with custom logic for most configuration options (or provide the ``$request`` or ``$response`` objects to your implementing class), such as switching storage modules based on which user performed the request.


.. _access-control-configuration:

Imbo access control - ``accessControl``
---------------------------------------

Imbo catalogs stored images under a ``user``. To add an image to a given user, you need a public and private key pair. This pair is used to sign requests to Imbo's API and ensures that the API can't be accessed without knowing the private key.

Multiple public keys can be given access to a user, and you can also configure a public key to have access to several users. It's important to note that a ``user`` doesn't have to be created in any way - as long as a public key is defined to have access to a given user, you're ready to start adding images.

Public keys can be configured to have varying degrees of access. For instance, you might want one public key for write operations (such as adding and deleting images) and a different public key for read operations (such as viewing images and applying transformations to them). Access is defined on a ``resource`` basis - which basically translates to an API endpoint and an HTTP method. To retrieve an image, for instance, you would give access to the ``image.get`` resource.

Specifying a long list of resources can get tedious, so Imbo also supports ``resource groups`` - basically just a list of different resources. When creating access rules for a public key, these can be used instead of specifying specific resources.

For the private keys you can for instance use a `SHA-256 <http://en.wikipedia.org/wiki/SHA-2>`_ hash of a random value. The private key is used by clients to sign requests, and if you accidentally give away your private key users can use it to delete all your images (given the public key it belongs to has write access). Make sure not to generate a private key that is easy to guess (like for instance the MD5 or SHA-256 hash of the public key). Imbo does not require the private key to be in a specific format, so you can also use regular passwords if you want. The key itself will never be a part of the payload sent to/from the server.

Imbo ships with a small command line tool that can be used to generate private keys for you using the `openssl_random_pseudo_bytes <http://php.net/openssl_random_pseudo_bytes>`_ function. The tool is located in the ``bin`` directory of the Imbo installation:

.. code-block:: bash

    $ ./bin/imbo generate-private-key
    3b98dde5f67989a878b8b268d82f81f0858d4f1954597cc713ae161cdffcc84a

The private key can be changed whenever you want as long as you remember to change it in both the server configuration and in the client you use. The user can not be changed easily as database and storage adapters use it when storing/fetching images and metadata.

Access control is managed by ``adapters``. The simplest adapter is the ``SimpleArrayAdapter``, which has a number of trade-offs in favor of being easy to set up. Mainly, it expects the public key to have the same name as the user it should have access to, and that the public key should be given full read+write access to all resources belonging to that user.

.. warning::
    It's not recommended that you use the same public key for both read and write operations. Read on to see how you can create different public keys for read and read/write access.

The adapter is set up using the ``accessControl`` key in your configuration file:

.. code-block:: php

    <?php
    return [
        // ...

        'accessControl' => function() {
            return new Imbo\Auth\AccessControl\Adapter\SimpleArrayAdapter([
                'some-user' => 'my-super-secret-private-key',
                'other-user' => 'other-super-secret-private-key',
            ]);
        },

        // ...
    ];

It's usually a good idea to have separate public keys for read-only and read+write operations. You can achieve this by using a more flexible access control adapter, such as the ``ArrayAdapter``:

.. code-block:: php

    <?php
    use Imbo\Auth\AccessControl\Adapter\ArrayAdapter,
        Imbo\Resource;

    return [
        // ...

        'accessControl' => function() {
            return new ArrayAdapter([
                [
                    'publicKey'  => 'some-read-only-pubkey',
                    'privateKey' => 'some-private-key',
                    'acl' => [[
                        'resources' => Resource::getReadOnlyResources(),
                        'users' => ['some-user']
                    ]]
                ],
                [
                    'publicKey'  => 'some-read-write-pubkey',
                    'privateKey' => 'some-other-private-key',
                    'acl' => [[
                        'resources' => Resource::getReadWriteResources(),
                        'users' => ['some-user']
                    ]]
                ]
            ]);
        }

        // ...
    ];

As you can see, the ``ArrayAdapter`` is much more flexible than the ``SimpleArrayAdapter``. The above example only shows part of this flexibility. You can also provide resource groups and multiple access control rules per public key. The following example shows this more clearly:

.. code-block:: php

    <?php
    use Imbo\Auth\AccessControl\Adapter\ArrayAdapter,
        Imbo\Resource

    return [
        // ...

        'accessControl' => function() {
            return new ArrayAdapter([
                [
                    // A unique public key matching the following regular expression: [A-Za-z0-9_-]{1,}
                    'publicKey'  => 'some-pubkey',

                    // Some form of private key
                    'privateKey' => 'some-private-key',

                    // Array of rules for this public key
                    'acl' => [
                        [
                            // An array of different resource names that the public key should have
                            // access to - see AdapterInterface::RESOURCE_* for available options.
                            'resources' => Resource::getReadOnlyResources(),

                            // Names of the users which the public key should have access to.
                            'users' => ['some', 'users'],
                        ],

                        // Multiple rules can be applied in order to make a single public key have
                        // different access rights on different users
                        [
                            'resources' => Resource::getReadWriteResources(),
                            'users' => ['different-user'],
                        ],

                        // You can also specify resource groups instead of explicitly setting them like
                        // in the above examples. Note that you cannot specify both resources and group
                        // in the same rule.
                        [
                            'group' => 'read-stats',
                            'users' => ['user1', 'user2']
                        ]
                    ]
                ]
            ], [
                // Second argument to the ArrayAdapter being the available resource groups
                // Format: 'name' => ['resource1', 'resource2']
                'read-stats' => ['user.get', 'user.head', 'user.options'],
            ]);
        },

        // ...
    ];

Imbo also ships with a MongoDB access control adapter, which is mutable. This means you can manipulate the access control rules on the fly, using Imbo's API. The adapter uses PHP's `mongo extension <http://pecl.php.net/package/mongo>`_. The following parameters are supported:

``databaseName``
    Name of the database to use. Defaults to ``imbo``.

``server``
    The server string to use when connecting. Defaults to ``mongodb://localhost:27017``.

``options``
    Options passed to the underlying adapter. Defaults to ``['connect' => true, 'timeout' => 1000]``. See the `manual for the MongoClient constructor <http://www.php.net/manual/en/mongoclient.construct.php>`_ for available options.

.. code-block:: php

    <?php
    return [
        // ...

        'accessControl' => function() {
            return new Imbo\Auth\AccessControl\Adapter\MongoDB([
                'databaseName' => 'imbo-acl'
            ]);
        },

        // ...
    ];

When using a mutable access control adapter, you will need to create an initial public key that can subsequently be used to create other public keys. The easiest way to create public keys when using a mutable adapter is to utilize the :ref:`add-public-key command <cli-add-public-key>` provided by the CLI tool that Imbo is shipped with.

.. _database-configuration:

Database configuration - ``database``
-------------------------------------

The database adapter you decide to use is responsible for storing metadata and basic image information, like width and height for example, along with the generated short URLs. Imbo ships with some different database adapters that you can use. Remember that you will not be able to switch the adapter whenever you want and expect all data to be automatically transferred. Choosing a database adapter should be a long term commitment unless you have migration scripts available.

In the default configuration file the :ref:`default-database-adapter` database adapter is used. You can choose to override this in your configuration file by specifying a different adapter. You can either specify an instance of a database adapter directly, or specify a closure that will return an instance of a database adapter when executed. Which database adapter to use is specified in the ``database`` key in the configuration array:

.. code-block:: php

    <?php
    return [
        // ...

        'database' => function() {
            return new Imbo\Database\MongoDB([
                'databaseName' => 'imbo',
            ]);
        },

        // or

        'database' => new Imbo\Database\MongoDB([
            'databaseName' => 'imbo',
        ]),

        // ...
    );

Below you will find documentation on the different database adapters Imbo ships with.

.. contents::
    :local:
    :depth: 1

.. _doctrine-database-adapter:

Doctrine
++++++++

This adapter uses the `Doctrine Database Abstraction Layer <http://www.doctrine-project.org/projects/dbal.html>`_. The options you pass to the constructor of this adapter is passed to the underlying classes, so have a look at the Doctrine DBAL documentation over at `doctrine-project.org <http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/index.html>`_. When using this adapter you need to create the required tables in the RDBMS first, as specified in the :ref:`database-setup` section.

Examples
^^^^^^^^

Here are some examples on how to use the Doctrine adapter in the configuration file:

1) Use a `PDO <http://php.net/pdo,>`_ instance to connect to a SQLite database:

.. code-block:: php

    <?php
    return [
        // ...

        'database' => function() {
            return new Imbo\Database\Doctrine([
                'pdo' => new PDO('sqlite:/path/to/database'),
            ]);
        },

        // ...
    ];

2) Connect to a MySQL database using PDO:

.. code-block:: php

    <?php
    return [
        // ...

        'database' => function() {
            return new Imbo\Database\Doctrine([
                'dbname'   => 'database',
                'user'     => 'username',
                'password' => 'password',
                'host'     => 'hostname',
                'driver'   => 'pdo_mysql',
            ]);
        },

        // ...
    ];

.. _mongodb-database-adapter:
.. _default-database-adapter:

MongoDB
+++++++

This adapter uses PHP's `mongo extension <http://pecl.php.net/package/mongo>`_ to store data in `MongoDB <http://www.mongodb.org/>`_. The following parameters are supported:

``databaseName``
    Name of the database to use. Defaults to ``imbo``.

``server``
    The server string to use when connecting. Defaults to ``mongodb://localhost:27017``.

``options``
    Options passed to the underlying adapter. Defaults to ``['connect' => true, 'timeout' => 1000]``. See the `manual for the MongoClient constructor <http://www.php.net/manual/en/mongoclient.construct.php>`_ for available options.

Examples
^^^^^^^^

1) Connect to a local MongoDB instance using the default ``databaseName``:

.. code-block:: php

    <?php
    return [
        // ...

        'database' => function() {
            return new Imbo\Database\MongoDB();
        },

        // ...
    ];

2) Connect to a `replica set <http://www.mongodb.org/display/DOCS/Replica+Sets>`_:

.. code-block:: php

    <?php
    return [
        // ...

        'database' => function() {
            return new Imbo\Database\MongoDB([
                'server' => 'mongodb://server1,server2,server3',
                'options' => [
                    'replicaSet' => 'nameOfReplicaSet',
                ],
            ]);
        },

        // ...
    ];

Mongo
+++++

This adapter uses PHP's `mongodb extension <http://pecl.php.net/package/mongodb>`_. It can be configured in the same was as the :ref:`mongodb-database-adapter` adapter.

Custom database adapter
+++++++++++++++++++++++

If you need to create your own database adapter you need to create a class that implements the ``Imbo\Database\DatabaseInterface`` interface, and then specify that adapter in the configuration:

.. code-block:: php

    <?php
    return [
        // ...

        'database' => function() {
            return new My\Custom\Adapter([
                'some' => 'option',
            ]);
        },

        // ...
    ];

You can read more about how to achieve this in the :doc:`../develop/custom_adapters` chapter.

.. _storage-configuration:

Storage configuration - ``storage``
-----------------------------------

Storage adapters are responsible for storing the original images you put into Imbo. As with the database adapter it is not possible to simply switch the adapter without having migration scripts available to move the stored images. Choose an adapter with care.

In the default configuration file the :ref:`default-storage-adapter` storage adapter is used. You can choose to override this in your configuration file by specifying a different adapter. You can either specify an instance of a storage adapter directly, or specify a closure that will return an instance of a storage adapter when executed. Which storage adapter to use is specified in the ``storage`` key in the configuration array:

.. code-block:: php

    <?php
    return [
        // ...

        'storage' => function() {
            return new Imbo\Storage\Filesystem([
                'dataDir' => '/path/to/images',
            ]);
        },

        // or

        'storage' => new Imbo\Storage\Filesystem([
            'dataDir' => '/path/to/images',
        ]),

        // ...
    ];

Below you will find documentation on the different storage adapters Imbo ships with.

.. contents::
    :local:
    :depth: 1

.. _s3-storage-adapter:

Amazon Simple Storage Service
+++++++++++++++++++++++++++++

This adapter stores your images in a bucket in the Amazon Simple Storage Service (S3). The parameters are:

``key``
    Your AWS access key

``secret``
    Your AWS secret key

``bucket``
    The name of the bucket you want to store your images in. Imbo will **not** create this for you.

This adapter creates subdirectories in the bucket in the same fashion as the :ref:`Filesystem storage adapter <filesystem-storage-adapter>` stores the files on the local filesystem.

Examples
^^^^^^^^

.. code-block:: php

    <?php
    return [
        // ...

        'storage' => function() {
            new Imbo\Storage\S3([
                'key' => '<aws access key>'
                'secret' => '<aws secret key>',
                'bucket' => 'my-imbo-bucket',
            ]);
        },

        // ...
    ];

Doctrine
++++++++

This adapter uses the `Doctrine Database Abstraction Layer <http://www.doctrine-project.org/projects/dbal.html>`_. The options you pass to the constructor of this adapter is passed to the underlying classes, so have a look at the Doctrine DBAL documentation over at `doctrine-project.org <http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/index.html>`_. When using this adapter you need to create the required tables in the RDBMS first, as specified in the :ref:`database-setup` section.

Examples
^^^^^^^^

Here are some examples on how to use the Doctrine adapter in the configuration file:

1) Use a PDO instance to connect to a SQLite database:

.. code-block:: php

    <?php
    return [
        // ...

        'storage' => function() {
            return new Imbo\Storage\Doctrine([
                'pdo' => new PDO('sqlite:/path/to/database'),
            ]);
        },

        // ...
    ];

2) Connect to a MySQL database using PDO:

.. code-block:: php

    <?php
    return [
        // ...

        'storage' => function() {
            return new Imbo\Storage\Doctrine([
                'dbname'   => 'database',
                'user'     => 'username',
                'password' => 'password',
                'host'     => 'hostname',
                'driver'   => 'pdo_mysql',
            ]);
        },

        // ...
    ];

.. _filesystem-storage-adapter:

Filesystem
++++++++++

This adapter simply stores all images on the file system. It has a single parameter, and that is the base directory of where you want your images stored:

``dataDir``
    The base path where the images are stored.

This adapter is configured to create subdirectories inside of ``dataDir`` based on the user and the checksum of the images added to Imbo. The algorithm that generates the path simply takes the three first characters of the user and creates directories for each of them, then the complete user, then a directory of each of the first characters in the image identifier, and lastly it stores the image in a file with a filename equal to the image identifier itself. For instance, an image stored under the user ``foobar`` with the image identifier ``5c01e554-9fca-4231-bb95-a6eabf259b64`` would be stored as ``<dataDir>/f/o/o/foobar/5/c/0/5c01e554-9fca-4231-bb95-a6eabf259b64``.

Examples
^^^^^^^^

1) Store images in ``/path/to/images``:

.. code-block:: php

    <?php
    return [
        // ...

        'storage' => function() {
            new Imbo\Storage\Filesystem([
                'dataDir' => '/path/to/images',
            ]);
        },

        // ...
    ];

.. _gridfs-storage-adapter:
.. _default-storage-adapter:

GridFS
++++++

The GridFS adapter is used to store the images in MongoDB using the `GridFS specification <http://www.mongodb.org/display/DOCS/GridFS>`_. This adapter has the following parameters:

``databaseName``
    The name of the database to store the images in. Defaults to ``imbo_storage``.

``server``
    The server string to use when connecting to MongoDB. Defaults to ``mongodb://localhost:27017``

``options``
    Options passed to the underlying adapter. Defaults to ``['connect' => true, 'timeout' => 1000]``. See the `manual for the MongoClient constructor <http://www.php.net/manual/en/mongoclient.construct.php>`_ for available options.

Examples
^^^^^^^^

1) Connect to a local MongoDB instance using the default ``databaseName``:

.. code-block:: php

    <?php
    return [
        // ...

        'storage' => function() {
            return new Imbo\Storage\GridFS();
        },

        // ...
    ];

2) Connect to a replica set:

.. code-block:: php

    <?php
    return [
        // ...

        'storage' => function() {
            return new Imbo\Storage\GridFS([
                'server' => 'mongodb://server1,server2,server3',
                'options' => [
                    'replicaSet' => 'nameOfReplicaSet',
                ],
            ]);
        },

        // ...
    ];

Custom storage adapter
++++++++++++++++++++++

If you need to create your own storage adapter you need to create a class that implements the ``Imbo\Storage\StorageInterface`` interface, and then specify that adapter in the configuration:

.. code-block:: php

    <?php
    return [
        // ...

        'storage' => function() {
            return new My\Custom\Adapter([
                'some' => 'option',
            ]);
        },

        // ...
    ];

You can read more about how to achieve this in the :doc:`../develop/custom_adapters` chapter.

.. _image-identifier-generation:

Image identifier generation - ``imageIdentifierGenerator``
----------------------------------------------------------

By default, Imbo will generate a random string of characters as the image identifier for added images. These are in the RegExp range ``[A-Za-z0-9_-]`` and by default, the identifier will be 12 characters long.

You can easily change the generation process to a different method. Imbo currently ships with two generators:

RandomString
++++++++++++

The default, as stated above. This generator has the following parameters:

``length``
    The length of the randomly generated string. Defaults to ``12``.

Uuid
++++

Generates 36-character v4 UUIDs, for instance ``f47ac10b-58cc-4372-a567-0e02b2c3d479``. This generator does not have any parameters.

Usage:

.. code-block:: php

    <?php
    return [
        // ...

        'imageIdentifierGenerator' => new Imbo\Image\Identifier\Generator\Uuid(),

        // ...
    ];

Custom generators
+++++++++++++++++

To create your own custom image identifier generators, simply create a class that implements ``Imbo\Image\Identifier\Generator\GeneratorInterface`` and ensure that the identifiers generated are in the character range ``[A-Za-z0-9_-]`` and are between one and 255 characters long.

.. _configuration-http-cache-headers:

HTTP cache headers - ``httpCacheHeaders``
-----------------------------------------

Imbo ships with reasonable defaults for which HTTP cache header settings it sends to clients. For some resources, however, it can be difficult to figure out a good middle ground between clients asking too often and too rarely. For instance, the ``images`` resource will change every time a new image has been added - but whether that happens once a second or once a year is hard to know.

To ensure that clients get fresh responses, Imbo sends ``max-age=0, must-revalidate`` on these kind of resources. You can however override these defaults in the configuration. For instance, if you wanted to set the ``max-age`` to 30 seconds, leave it up to the client if it should re-validate and tell intermediary proxies that this response is private, you could set the configuration to the following:

.. code-block:: php

    <?php
    return [
        // ...

        'httpCacheHeaders' => [
            'maxAge' => 30,
            'mustRevalidate' => false,
            'public' => false,
        ],

        // ...
    ];

.. _configuration-content-negotiation:

Content negotiation for images - ``contentNegotiateImages``
-----------------------------------------------------------

By default, Imbo will do content negotiation for images. In other words, if a request is sent for an image with the ``Accept``-header ``image/jpeg``, it will try to deliver the image in JPEG-format.

If what you want is for images to be delivered in the format they were uploaded in, you can set ``contentNegotiateImages`` to ``false`` in the configuration. This will also ensure Imbo does not include ``Accept`` in the ``Vary``-header for image requests, which will make caching behind reverse proxies more efficient.

You are still able to convert between formats by specifying an extension when requesting the image (`.jpg`, `.png`, `.gif` etc).

.. _configuration-trusted-proxies:

Trusted proxies - ``trustedProxies``
------------------------------------

If you find yourself behind some sort of reverse proxy (like a load balancer), certain header information may be sent to you using special ``X-Forwarded-*`` headers. For example, the ``Host`` HTTP-header is usually used to return the requested host. But when you're behind a proxy, the true host may be stored in an ``X-Forwarded-Host`` header.

Since HTTP headers can be spoofed, Imbo does not trust these proxy headers by default. If you are behind a proxy, you should manually whitelist your proxy. This can be done by defining the proxies IP addresses and/or using CIDR notations. Example:

.. code-block:: php

    <?php
    return [
        // ...

        'trustedProxies' => ['192.0.0.1', '10.0.0.0/8'],

        // ...
    ];

.. note:: Not all proxies set the required ``X-Forwarded-*`` headers by default. A search for ``X-Forwarded-Proto <your proxy here>`` usually gives helpful answers to how you can add them to incoming requests.

.. _configuration-authentication-protocol:

Authentication protocol - ``authentication``
--------------------------------------------

Imbo generates access tokens and authentication signatures based on the incoming URL, and includes the protocol (by default). This can sometimes be problematic, for instance when Imbo is behind a load balancer which doesn't send ``X-Forwarded-Proto`` header, or if you want to use protocol-less image URLs on the client side (``//imbo.host/users/some-user/images/img``).

Setting the ``protocol`` option under ``authentication`` allows you to control how Imbo's authentication should behave. The option has the following possible values:

``incoming``
    Will try to detect the incoming protocol - this is based on ``$_SERVER['HTTPS']`` or the ``X-Forwarded-Proto`` header (given the ``trustedProxies`` option is configured). This is the default value.

``both``
    Will try to match based on both HTTP and HTTPS protocols and allow the request if any of them yields the correct signature/access token.

``http``
    Will always use ``http`` as the protocol, replacing ``https`` with ``http`` in the incoming URL, if that is the case.

``https``
    Will always use ``https`` as the protocol, replacing ``http`` with ``https`` in the incoming URL, if that is the case.

Example usage:

.. code-block:: php

    <?php
    return [
        // ...

        'authentication' => [
            'protocol' => 'both',
        ],

        // ...
    ];

.. _event-listeners-configuration:

Event listeners - ``eventListeners``
------------------------------------

Imbo support event listeners that you can use to hook into Imbo at different phases without having to edit Imbo itself. An event listener is simply a piece of code that will be executed when a certain event is triggered from Imbo. Event listeners are added to the ``eventListeners`` part of the configuration array as associative arrays. If you want to disable some of the default event listeners simply specify the same key in your configuration file and set the value to ``null`` or ``false``. Keep in mind that not all event listeners should be disabled.

Event listeners can be configured in the following ways:

1) A string representing a class name of a class implementing the ``Imbo\EventListener\ListenerInteface`` interface:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'accessToken' => 'Imbo\EventListener\AccessToken',
        ],

        // ...
    ];

2) Use an instance of a class implementing the ``Imbo\EventListener\ListenerInterface`` interface:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'accessToken' => new Imbo\EventListener\AccessToken(),
        ],

        // ...
    ];

3) A closure returning an instance of a class implementing the ``Imbo\EventListener\ListenerInterface`` interface:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'accessToken' => function() {
                return new Imbo\EventListener\AccessToken();
            },
        ],

        // ...
    ];

4) Use a class implementing the ``Imbo\EventListener\ListenerInterface`` interface together with an optional user filter:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'maxImageSize' => [
                'listener' => new Imbo\EventListener\MaxImageSize(1024, 768),
                'users' => [
                    'whitelist' => ['user'],
                    // 'blacklist' => ['someotheruser'],
                ],
                // 'params' => [ ... ]
            ],
        ],

        // ...
    ];

where ``listener`` is one of the following:

a) a string representing a class name of a class implementing the ``Imbo\EventListener\ListenerInterface`` interface
b) an instance of the ``Imbo\EventListener\ListenerInterface`` interface
c) a closure returning an instance ``Imbo\EventListener\ListenerInterface``

The ``users`` element is an array that you can use if you want your listener to only be triggered for some users. The value of this is an array with two elements, ``whitelist`` and ``blacklist``, where ``whitelist`` is an array of users you **want** your listener to trigger for, and ``blacklist`` is an array of users you **don't want** your listener to trigger for. ``users`` is optional, and per default the listener will trigger for all users.

There also exists a ``params`` key that can be used to specify parameters for the event listener, if you choose to specify the listener as a string in the ``listener`` key:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'maxImageSize' => [
                'listener' => 'Imbo\EventListener\MaxImageSize',
                'users' => [
                    'whitelist' => ['user'],
                    // 'blacklist' => ['someotheruser'],
                ],
                'params' => [
                    'width' => 1024,
                    'height' => 768,
                ]
            ],
        ],

        // ...
    ];

The value of the ``params`` array will be sent to the constructor of the event listener class.

5) Use a closure directly:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'customListener' => [
                'callback' => function(Imbo\EventManager\EventInterface $event) {
                    // Custom code
                },
                'events' => ['image.get'],
                'priority' => 1,
                'users' => [
                    'whitelist' => ['user'],
                    // 'blacklist' => ['someotheruser'],
                ],
            ],
        ],

        // ...
    ];

where ``callback`` is the code you want executed, and ``events`` is an array of the events you want it triggered for. ``priority`` is the priority of the listener and defaults to 0. The higher the number, the earlier in the chain your listener will be triggered. This number can also be negative. Imbo's internal event listeners uses numbers between 0 and 100. ``users`` uses the same format as described above. If you use this method, and want your callback to trigger for multiple events with different priorities, specify an associative array in the ``events`` element, where the keys are the event names, and the values are the priorities for the different events. This way of attaching event listeners should mostly be used for quick and temporary solutions.

All event listeners will receive an event object (which implements ``Imbo\EventManager\EventInterface``), that is described in detail in the :ref:`the-event-object` section.

.. _listeners-added-by-default:

Listeners added by default
++++++++++++++++++++++++++

The default configuration file includes some event listeners by default:

* :ref:`access-token-event-listener`
* :ref:`authenticate-event-listener`
* :ref:`stats-access-event-listener`
* :ref:`imagick-event-listener`

as well as event listeners for image transformations:

.. _image-transformation-names:

* :ref:`autoRotate <auto-rotate-transformation>`
* :ref:`border <border-transformation>`
* :ref:`canvas <canvas-transformation>`
* :ref:`compress <compress-transformation>`
* :ref:`convert <convert-transformation>`
* :ref:`crop <crop-transformation>`
* :ref:`desaturate <desaturate-transformation>`
* :ref:`flipHorizontally <flip-horizontally-transformation>`
* :ref:`flipVertically <flip-vertically-transformation>`
* :ref:`maxSize <max-size-transformation>`
* :ref:`resize <resize-transformation>`
* :ref:`rotate <rotate-transformation>`
* :ref:`sepia <sepia-transformation>`
* :ref:`smartSize <smartsize-transformation>`
* :ref:`strip <strip-transformation>`
* :ref:`thumbnail <thumbnail-transformation>`
* :ref:`transpose <transpose-transformation>`
* :ref:`transverse <transverse-transformation>`
* :ref:`vignette <vignette-transformation>`
* :ref:`watermark <watermark-transformation>`

Read more about these listeners (and more) in the :doc:`../installation/event_listeners` and :doc:`../usage/image-transformations` chapters. If you want to disable any of these you could do so in your configuration file in the following way:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'accessToken' => null,
            'auth' => null,
            'statsAccess' => null,
        ],

        // ...
    ];

.. warning:: Do not disable the event listeners used in the example above unless you are absolutely sure about the consequences. Your images can potentially be deleted by anyone.
.. warning:: Disabling image transformation event listeners is not recommended.

.. _image-transformations-config:

.. _configuration-event-listener-initializers:

Event listener initializers - ``eventListenerInitializers``
-----------------------------------------------------------

Some event listeners might require custom initialization, and if you don't want to do this in-line in the configuration, Imbo supports event listener initializer classes. This is handled via the ``eventListenerInitializers`` key. The value of this element is an associative array where the keys identify the initializers (only used in the configuration itself), and the values are strings representing class names, or implementations of the ``Imbo\EventListener\Initializer\InitializerInterface`` interface. If you specify strings the classes you refer to must also implement this interface.

The interface has a single method called ``initialize`` and receives instances of event listeners implementing the ``Imbo\EventListener\ListenerInterface`` interface. This method is called once for each event listener instantiated by Imbo's event manager. Example:

.. code-block:: php

    <?php
    // Some event listener
    class Listener implements Imbo\EventListener\ListenerInterface {
        public function setDependency($dependency) {
            // ...
        }

        // ...
    }

    class OtherListener implements Imbo\EventListener\ListenerInterface {
        public function setDependency($dependency) {
            // ...
        }

        // ...
    }

    // Event listener initializer
    class Initializer implements Imbo\EventListener\Initializer\InitializerInterface {
        private $dependency;

        public function __construct() {
            $this->dependency = new SomeDependency();
        }

        public function initialize(Imbo\EventListener\ListenerInterface $listener) {
            if ($listener instanceof Listener || $listener instanceof OtherListener) {
                $listener->setDependency($this->dependency);
            }
        }
    }

    // Configuration
    return [
        'eventListeners' => [
            'customListener' => 'Listener',
            'otherCustomListener' => 'OtherListener',
        ],

        'eventListenerInitializers' => [
            'initializerForCustomListener' => 'Initializer',
        ],
    ];

In the above example the ``Initializer`` class will be instantiated by Imbo, and in the ``__construct`` method it will create an instance of some dependency. When the event manager creates the instances of the two event listeners these will in turn be sent to the ``initialize`` method, and the same dependency will be injected into both listeners. An alternative way to accomplish this by using Closures in the configuration could look something like this:

.. code-block:: php

    <?php
    $dependency = new SomeDependency();

    return [
        'eventListeners' => [
            'customListener' => function() use ($dependency) {
                $listener = new Listener();
                $listener->setDependency($dependency);

                return $listener;
            },
            'otherCustomListener' => function() use ($dependency) {
                $listener = new OtherListener();
                $listener->setDependency($dependency);

                return $listener;
            },
        ],
    ];

Imbo itself includes an event listener initializer in the default configuration that is used to inject the same instance of Imagick to all image transformations.

.. note:: Only event listeners specified as strings (class names) in the configuration will be instantiated by Imbo, so event listeners instantiated in the configuration array, either directly or via a Closures, will not be initialized by the configured event listener initializers.

.. _transformation-presets-configuration:

Image transformation presets - ``transformationPresets``
--------------------------------------------------------

Through the configuration you can also combine image transformations to make presets (transformation chains). This is done via the ``transformationPresets`` key:

.. code-block:: php

    <?php
    return [
        // ...

        'transformationPresets' => [
            'graythumb' => [
                'thumbnail',
                'desaturate',
            ],
            // ...
        ],

        // ...
    ];

where the keys are the names of the transformations as specified in the URL, and the values are arrays containing other transformation names (as used in the ``eventListeners`` part of the configuration). You can also specify hard coded parameters for the presets if some of the transformations in the chain supports parameters:

.. code-block:: php

    <?php
    return [
        // ...

        'transformationPresets' => [
            'fixedGraythumb' => [
                'thumbnail' => [
                    'width' => 50,
                    'height' => 50,
                ],
                'desaturate',
            ],
            // ...
        ],

        // ...
    ];

By doing this the ``thumbnail`` part of the ``fixedGraythumb`` preset will ignore the ``width`` and ``height`` query parameters, if present. By only specifying for instance ``'width' => 50`` in the configuration the height of the thumbnail can be adjusted via the query parameter, but the ``width`` is fixed.

.. note:: The URLs will stay the same if you change the transformation chain in a preset. Keep this in mind if you use for instance Varnish or some other HTTP accelerator in front of your web server(s).

.. _resource-configuration:

Custom resources and routes - ``resources`` and ``routes``
----------------------------------------------------------

.. warning:: Custom resources and routes is an experimental and advanced way of extending Imbo, and requires extensive knowledge of how Imbo works internally. This feature can potentially be removed in future releases, so only use this for testing purposes.

If you need to create a custom route you can attach a route and a custom resource class using the configuration. Two keys exists for this purpose: ``resources`` and ``routes``:

.. code-block:: php

    <?php
    return [
        // ...

        'resources' => [
            'users' => new ImboUsers();

            // or

            'users' => function() {
                return new ImboUsers();
            },

            // or

            'users' => 'ImboUsers',
        ],

        'routes' => [
            'users' => '#^/users(\.(?<extension>json|xml))?$#',
        ],

        // ...
    ];

In the above example we are creating a route for Imbo using a regular expression, called ``users``. The route itself will match the following three requests:

* ``/users``
* ``/users.json``
* ``/users.xml``

When a request is made against any of these endpoints Imbo will try to access a resource that is specified with the same key (``users``). The value specified for this entry in the ``resources`` array can be:

1) a string representing the name of the resource class
2) an instance of a resource class
3) an anonymous function that, when executed, returns an instance of a resource class

The resource class must implement the ``Imbo\Resource\ResourceInterface`` interface to be able to response to a request.

Below is an example implementation of the ``ImboUsers`` resource used in the above configuration:

.. code-block:: php

    <?php
    use Imbo\Resource\ResourceInterface,
        Imbo\EventManager\EventInterface,
        Imbo\Model\ListModel;

    class ImboUsers implements ResourceInterface {
        public function getAllowedMethods() {
            return ['GET'];
        }

        public static function getSubscribedEvents() {
            return [
                'users.get' => 'get',
            ];
        }

        public function get(EventInterface $event) {
            $model = new ListModel();
            $model->setList('users', 'user', array_keys($event->getConfig()['auth']));
            $event->getResponse()->setModel($model);
        }
    }

This resource informs Imbo that it supports ``HTTP GET``, and specifies a callback for the ``users.get`` event. The name of the event is the name specified for the resource in the configuration above, along with the HTTP method, separated with a dot.

In the ``get()`` method we are simply creating a list model for Imbo's response formatter, and we are supplying the keys from the ``auth`` part of your configuration file as data. When formatted as JSON the response looks like this:

.. code-block:: json

    {
      "users": [
        "someuser",
        "someotheruser"
      ]
    }

and the XML representation looks like this:

.. code-block:: xml

    <?xml version="1.0" encoding="UTF-8"?>
    <imbo>
      <users>
        <user>someuser</user>
        <user>someotheruser</user>
      </users>
    </imbo>

Feel free to experiment with this feature. If you end up creating a resource that you think should be a part of Imbo, send a `pull request on GitHub <https://github.com/imbo/imbo>`_.
