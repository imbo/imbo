.. _configuration:

Configuration
=============

Imbo ships with a default configuration file named ``config/config.default.php`` that Imbo will load. You can specify your own configuration file, ``config/config.php``, that Imbo will merge with the default. You should never update ``config/config.default.php``.

User key pairs
--------------

Every user that wants to store images in Imbo needs a public and private key pair. These keys are stored in the ``auth`` part of the configuration file:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'auth' => array(
            'username'  => '95f02d701b8dc19ee7d3710c477fd5f4633cec32087f562264e4975659029af7',
            'otheruser' => 'b312ff29d5da23dcd230b61ff4db1e2515c862b9fb0bb59e7dd54ce1e4e94a53',
        ),

        // ...
    );

The public keys can consist of the following characters:

* a-z (only lowercase is allowed)
* 0-9
* _ and -

and must be at least 3 characters long.

For the private keys you can for instance use a `SHA-256`_ hash of a random value. The private key is used by clients to sign requests, and if you accidentally give away your private key users can use it to delete all your images. Make sure not to generate a private key that is easy to guess (like for instance the MD5 or SHA-256 hash of the public key). Imbo does not require the private key to be in a specific format, so you can also use regular passwords if you want.

Imbo ships with a small command line tool that can be used to generate private keys for you using the `openssl_random_pseudo_bytes`_ function. The script is located in the `scripts` directory and does not require any arguments:

.. code-block:: bash

    $ php scripts/generatePrivateKey.php
    3b98dde5f67989a878b8b268d82f81f0858d4f1954597cc713ae161cdffcc84a

.. _SHA-256: http://en.wikipedia.org/wiki/SHA-2
.. _openssl_random_pseudo_bytes: http://php.net/openssl_random_pseudo_bytes

The private key can be changed whenever you want as long as you remember to change it in both the server configuration and in the client you use. The public key can not be changed easily as database and storage drivers use it when storing images and metadata.

Database configuration
----------------------

The database driver you decide to use is responsible for storing metadata and basic image information, like width and height for example, along with the generated short URLs. Imbo ships with some different implementations that you can use. Remember that you will not be able to switch the driver whenever you want and expect all data to be automatically transferred. Choosing a database driver should be a long term commitment unless you have migration scripts available.

In the default configuration file the :ref:`default-database-driver` storage driver is used, and it is returned via a Closure. You can choose to override this in your ``config.php`` file by specifying a closure that returns a different value, or you can specify an implementation of the ``Imbo\Database\DatabaseInterface`` interface directly. Which database driver to use is specified in the ``database`` key in the configuration array:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'database' => function() {
            return new Database\MongoDB(array(
                'databaseName'   => 'imbo',
            ));
        },

        // or

        'database' => new Database\MongoDB(array(
            'databaseName'   => 'imbo',
        )),

        // ...
    );

Available database drivers
++++++++++++++++++++++++++

The following database drivers are shipped with Imbo:

.. contents::
    :local:
    :depth: 1

.. _doctrine-database-driver:

Doctrine
^^^^^^^^

This driver uses the `Doctrine Database Abstraction Layer`_. The options you pass to the constructor of this driver is passed to the underlying classes, so have a look at the Doctrine-DBAL documentation over at `doctrine-project.org`_.

.. _Doctrine Database Abstraction Layer: http://www.doctrine-project.org/projects/dbal.html
.. _doctrine-project.org: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/index.html

Database schema
~~~~~~~~~~~~~~~

When using this driver you need to create a couple of tables in the `DBMS`_ you choose to use. Below you will find statements to create the necessary tables for `SQLite`_ and `MySQL`_.

.. _DBMS: http://en.wikipedia.org/wiki/Relational_database_management_system
.. _SQLite: http://www.sqlite.org/
.. _MySQL: http://www.mysql.com/

SQLite
''''''

.. code-block:: sql
    :linenos:

    CREATE TABLE IF NOT EXISTS imageinfo (
        id INTEGER PRIMARY KEY NOT NULL,
        publicKey TEXT NOT NULL,
        imageIdentifier TEXT NOT NULL,
        size INTEGER NOT NULL,
        extension TEXT NOT NULL,
        mime TEXT NOT NULL,
        added INTEGER NOT NULL,
        updated INTEGER NOT NULL,
        width INTEGER NOT NULL,
        height INTEGER NOT NULL,
        checksum TEXT NOT NULL,
        UNIQUE (publicKey,imageIdentifier)
    )

    CREATE TABLE IF NOT EXISTS metadata (
        id INTEGER PRIMARY KEY NOT NULL,
        imageId KEY INTEGER NOT NULL,
        tagName TEXT NOT NULL,
        tagValue TEXT NOT NULL
    )

    CREATE TABLE IF NOT EXISTS shorturl (
        shortUrlId TEXT PRIMARY KEY NOT NULL,
        publicKey TEXT NOT NULL,
        imageIdentifier TEXT NOT NULL,
        extension TEXT,
        query TEXT NOT NULL
    )

    CREATE INDEX shorturlparams ON shorturl (
        publicKey,
        imageIdentifier,
        extension,
        query
    )

MySQL
'''''

.. code-block:: sql
    :linenos:

    CREATE TABLE IF NOT EXISTS `imageinfo` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `publicKey` varchar(255) COLLATE utf8_danish_ci NOT NULL,
        `imageIdentifier` char(32) COLLATE utf8_danish_ci NOT NULL,
        `size` int(10) unsigned NOT NULL,
        `extension` varchar(5) COLLATE utf8_danish_ci NOT NULL,
        `mime` varchar(20) COLLATE utf8_danish_ci NOT NULL,
        `added` int(10) unsigned NOT NULL,
        `updated` int(10) unsigned NOT NULL,
        `width` int(10) unsigned NOT NULL,
        `height` int(10) unsigned NOT NULL,
        `checksum` char(32) COLLATE utf8_danish_ci NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `image` (`publicKey`,`imageIdentifier`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci AUTO_INCREMENT=1 ;

    CREATE TABLE IF NOT EXISTS `metadata` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `imageId` int(10) unsigned NOT NULL,
        `tagName` varchar(255) COLLATE utf8_danish_ci NOT NULL,
        `tagValue` varchar(255) COLLATE utf8_danish_ci NOT NULL,
        PRIMARY KEY (`id`),
        KEY `imageId` (`imageId`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci AUTO_INCREMENT=1 ;

    CREATE TABLE `shorturl` (
        `shortUrlId` char(7) COLLATE utf8_danish_ci NOT NULL,
        `publicKey` varchar(255) COLLATE utf8_danish_ci NOT NULL,
        `imageIdentifier` char(32) COLLATE utf8_danish_ci NOT NULL,
        `extension` char(3) COLLATE utf8_danish_ci DEFAULT NULL,
        `query` text COLLATE utf8_danish_ci NOT NULL,
        PRIMARY KEY (`shortUrlId`),
        KEY `params` (`publicKey`,`imageIdentifier`,`extension`,`query`(255))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

.. note:: Imbo will not create these tables automatically.

Examples
~~~~~~~~

Here are some examples on how to use the Doctrine driver in the configuration file:

1) Use a `PDO`_ instance to connect to a SQLite database:

.. _PDO: http://php.net/pdo

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'database' => function() {
            return new Database\Doctrine(array(
                'pdo' => new \PDO('sqlite:/path/to/database'),
            ));
        },

        // ...
    );

2) Connect to a MySQL database using PDO:

.. _PDO: http://php.net/pdo

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'database' => function() {
            return new Database\Doctrine(array(
                'dbname'   => 'database',
                'user'     => 'username',
                'password' => 'password',
                'host'     => 'hostname',
                'driver'   => 'pdo_mysql',
            ));
        },

        // ...
    );

.. _mongodb-database-driver:
.. _default-database-driver:

MongoDB
^^^^^^^

This driver uses PHP's `mongo extension`_ to store data in `MongoDB`_. The following parameters are supported:

.. _mongo extension: http://pecl.php.net/package/mongo
.. _MongoDB: http://www.mongodb.org/

``databaseName``
    Name of the database to use. Defaults to ``imbo``.

``server``
    The server string to use when connecting. Defaults to ``mongodb://localhost:27017``.

``options``
    Options passed to the underlying driver. Defaults to ``array('connect' => true, 'timeout' => 1000)``. See the `manual for the Mongo constructor`_ at `php.net <http://php.net>`_ for available options.

.. _manual for the Mongo constructor: http://php.net/manual/en/mongo.construct.php

Examples
~~~~~~~~

1) Connect to a local MongoDB instance using the default ``databaseName``:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'database' => function() {
            return new Database\MongoDB();
        },

        // ...
    );

2) Connect to a `replica set`_:

.. _replica set: http://www.mongodb.org/display/DOCS/Replica+Sets

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'database' => function() {
            return new Database\MongoDB(array(
                'server' => 'mongodb://server1,server2,server3',
                'options' => array(
                    'replicaSet' => 'nameOfReplicaSet',
                ),
            ));
        },

        // ...
    );

Storage configuration
---------------------

Storage drivers are responsible for storing the original images you put into imbo. Like with the database driver it is not possible to simply switch a driver without having migration scripts available to move the stored images. Choose a driver with care.

In the default configuration file the :ref:`default-storage-driver` storage driver is used, and it is returned via a Closure. You can choose to override this in your ``config.php`` file by specifying a closure that returns a different value, or you can specify an implementation of the ``Imbo\Storage\StorageInterface`` interface directly. Which storage driver to use is specified in the ``storage`` key in the configuration array:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'storage' => new function() {
            return new Storage\Filesystem(array(
                'dataDir' => '/path/to/images',
            ));
        },

        // ...
    );

Available storage drivers
+++++++++++++++++++++++++

The following storage drivers are shipped with Imbo:

.. contents::
    :local:
    :depth: 1

.. _doctrine-storage-driver:

Doctrine
^^^^^^^^

This driver uses the `Doctrine Database Abstraction Layer`_. The options you pass to the constructor of this driver is passed to the underlying classes, so have a look at the Doctrine-DBAL documentation over at `doctrine-project.org`_.

.. _Doctrine Database Abstraction Layer: http://www.doctrine-project.org/projects/dbal.html
.. _doctrine-project.org: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/index.html

Database schema
~~~~~~~~~~~~~~~

When using this driver you need to create a table in the `DBMS`_ you choose to use. Below you will find a statement to create this table in `SQLite`_ and `MySQL`_.

SQLite
''''''

.. code-block:: sql
    :linenos:

    CREATE TABLE storage_images (
        publicKey TEXT NOT NULL,
        imageIdentifier TEXT NOT NULL,
        data BLOB NOT NULL,
        updated INTEGER NOT NULL,
        PRIMARY KEY (publicKey,imageIdentifier)
    )

MySQL
'''''

.. code-block:: sql
    :linenos:

    CREATE TABLE IF NOT EXISTS `storage_images` (
        `publicKey` varchar(255) COLLATE utf8_danish_ci NOT NULL,
        `imageIdentifier` char(32) COLLATE utf8_danish_ci NOT NULL,
        `data` blob NOT NULL,
        `updated` int(10) unsigned NOT NULL,
        PRIMARY KEY (`publicKey`,`imageIdentifier`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

.. note:: Imbo will not create the table automatically.

Examples
~~~~~~~~

Here are some examples on how to use the Doctrine driver in the configuration file:

1) Use a `PDO`_ instance to connect to a SQLite database:

.. _PDO: http://php.net/pdo

.. code-block:: php

    <?php
    namespace Imbo;

    return array(
        // ...

        'storage' => function() {
            return new Storage\Doctrine(array(
                'pdo' => new \PDO('sqlite:/path/to/database'),
            ));
        },

        // ...
    );

2) Connect to a MySQL database using PDO:

.. _PDO: http://php.net/pdo

.. code-block:: php

    <?php
    namespace Imbo;

    return array(
        // ...

        'storage' => function() {
            return new Storage\Doctrine(array(
                'dbname'   => 'database',
                'user'     => 'username',
                'password' => 'password',
                'host'     => 'hostname',
                'driver'   => 'pdo_mysql',
            ));
        },

        // ...
    );

.. _filesystem-storage-driver:

Filesystem
^^^^^^^^^^

This driver simply stores all images on the file system. This driver only has one parameter, and that is the directory where you want your images stored:

``dataDir``
    The base path where the images are stored.

This driver is configured to create subdirectories inside of ``dataDir`` based on the public key of the user and the checksum of the images added to Imbo. If you have configured this driver with ``/path/to/images`` as ``dataDir`` and issue the following command:

.. code-block:: bash

    $ curl -XPUT http://imbo/users/username/images/bbd9ae7bbfcefb0cc9a52f03f89dd3f9 --data-binary @someImage.jpg

the image will be stored in:

``/path/to/images/u/s/e/username/b/b/d/bbd9ae7bbfcefb0cc9a52f03f89dd3f9``

The algorithm that generates the path simply takes the three first characters of ``<user>`` and creates directories for each of them, then the full public key, then a directory of each of the first characters in ``<image>`` and lastly it stores the image in a file with a filename equal to ``<image>``.

Read more about the API in the :doc:`api` topic.

Examples
~~~~~~~~

Default configuration:

.. code-block:: php

    <?php
    namespace Imbo;

    return array(
        // ...

        'storage' => function() {
            new Storage\Filesystem(array(
                'dataDir' => '/path/to/images',
            ));
        },

        // ...
    );

.. _gridfs-storage-driver:
.. _default-storage-driver:

GridFS
^^^^^^

The GridFS driver is used to store the images in MongoDB using the `GridFS specification`_. This driver has the following parameters:

.. _GridFS specification: http://www.mongodb.org/display/DOCS/GridFS

``databaseName``
    The name of the database to store the images in. Defaults to ``imbo_storage``.

``server``
    The server string to use when connecting to MongoDB. Defaults to ``mongodb://localhost:27017``

``options``
    Options passed to the underlying driver. Defaults to ``array('connect' => true, 'timeout' => 1000)``. See the `manual for the Mongo constructor`_ at `php.net <http://php.net>`_ for available options.

Examples
~~~~~~~~

1) Connect to a local MongoDB instance using the default ``databaseName``:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'storage' => function() {
            return new Storage\GridFS();
        },

        // ...
    );

2) Connect to a `replica set`_:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'storage' => function() {
            return new Storage\GridFS(array(
                'server' => 'mongodb://server1,server2,server3',
                'options' => array(
                    'replicaSet' => 'nameOfReplicaSet',
                ),
            ));
        },

        // ...
    );

.. _configuration-event-listeners:

Event listeners
---------------

Imbo also supports event listeners that you can use to hook into Imbo at different phases without having to edit Imbo itself. An event listener is simply a piece of code that will be executed when a certain event is triggered from Imbo. Event listeners are added to the ``eventListeners`` part of the configuration array as associative arrays. The keys are short names used to identify the listeners, and are not really used for anything in the Imbo application, but exists so you can override/disable event listeners specified in ``config.default.php``. If you want to disable the default event listeners simply specify the same key in the ``config.php`` file and set the value to ``null`` or ``false``.

Event listeners can be added in the following ways:

1) Use an instance of a class implementing the ``Imbo\EventListener\ListenerInterface`` interface:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            'accessToken' => new EventListener\AccessToken(),
        ),

        // ...
    );

2) A closure returning an instance of the ``Imbo\EventListener\ListenerInterface`` interface

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            'accessToken' => function() {
                return new EventListener\AccessToken();
            },
        ),

        // ...
    );

3) Use an instance of a class implementing the ``Imbo\EventListener\ListenerInterface`` interface together with a public key filter:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            'maxImageSize' => array(
                'listener' => new EventListener\MaxImageSize(1024, 768),
                'publicKeys' => array(
                    'include' => array('user'),
                    // 'exclude' => array('someotheruser'),
                ),
            ),
        ),

        // ...
    );

where ``listener`` is an instance of the ``Imbo\EventListener\ListenerInterface`` interface, and ``publicKeys`` is an array that you can use if you want your listener to only be triggered for some users (public keys). The value of this is an array with one of two keys: ``include`` or ``exclude`` where ``include`` is an array you want your listener to trigger for, and ``exclude`` is an array of users you don't want your listener to trigger for. ``publicKeys`` is optional, and per default the listener will trigger for all users.

4) Use a `closure`_:

.. _closure: http://php.net/manual/en/functions.anonymous.php

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            'customListener' => array(
                'callback' => function(EventManager\EventInterface $event) {
                    // Custom code
                },
                'events' => array('image.get'),
                'priority' => 1,
                'publicKeys' => array(
                    'include' => array('user'),
                    // 'exclude' => array('someotheruser'),
                ),
            ),
        ),

        // ...
    );

where ``callback`` is the code you want executed, and ``events`` is an array of the events you want it triggered for. ``priority`` is the priority of the listener and defaults to 1. The higher the number, the earlier in the chain your listener will be triggered. This number can also be negative. Imbo's internal event listeners uses numbers between 1 and 100. ``publicKeys`` uses the same format as described above.

Events
++++++

When configuring an event listener you need to know about the events that Imbo triggers. The most important events are combinations of the accessed resource along with the HTTP method used. Imbo currently provides five resources:

* :ref:`stats <stats-resource>`
* :ref:`status <status-resource>`
* :ref:`user <user-resource>`
* :ref:`images <images-resource>`
* :ref:`image <image-resource>`
* :ref:`metadata <metadata-resource>`

Examples of events that is triggered:

* ``image.get``
* ``image.put``
* ``image.delete``

As you can see from the above examples the events are built up by the resource name and the HTTP method, separated by ``.``.

Some other notable events:

* ``storage.image.insert``
* ``storage.image.load``
* ``storage.image.delete``
* ``db.image.insert``
* ``db.image.load``
* ``db.image.delete``
* ``db.metadata.update``
* ``db.metadata.load``
* ``db.metadata.delete``
* ``route``
* ``response.send``

Below you will see the different event listeners that Imbo ships with and the events they subscribe to.

Event listeners
+++++++++++++++

Imbo ships with a collection of event listeners for you to use. Some of them are enabled in the default configuration file.

.. contents::
    :local:
    :depth: 1

.. _access-token-event-listener:

Access token
^^^^^^^^^^^^

This event listener enforces the usage of access tokens on all read requests against user-specific resources. You can read more about how the actual access tokens works in the :ref:`access-tokens` topic in the :doc:`api` section.

To enforce the access token check for all read requests this event listener subscribes to the following events:

* ``user.get``
* ``images.get``
* ``image.get``
* ``metadata.get``
* ``user.head``
* ``images.head``
* ``image.head``
* ``metadata.head``

This event listener has a single parameter that can be used to whitelist and/or blacklist certain image transformations, used when the current request is against an image resource. The parameter is an array with a single key: ``transformations``. This is another array with two keys: ``whitelist`` and ``blacklist``. These two values are arrays where you specify which transformation(s) to whitelist or blacklist. The names of the transformations are the same as the ones used in the request. See :ref:`image-transformations` for a complete list of the supported transformations.

Use ``whitelist`` if you want the listener to skip the access token check for certain transformations, and ``blacklist`` if you want it to only check certain transformations:

.. code-block:: php

    array('transformations' => array(
        'whitelist' => array(
            'border',
        )
    ))

means that the access token will **not** be enforced for the :ref:`border-transformation` transformation.

.. code-block:: php

    array('transformations' => array(
        'blacklist' => array(
            'border',
        )
    ))

means that the access token will be enforced **only** for the :ref:`border-transformation` transformation.

If both ``whitelist`` and ``blacklist`` are specified all transformations will require an access token unless it's included in ``whitelist``.

This event listener is included in the default configuration file without specifying any filters (which means that the access token will be enforced for all requests):

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            'accessToken' => function() {
                return new EventListener\AccessToken();
            },
        ),

        // ...
    );

Disable this event listener with care. Clients can easily `DDoS`_ your installation if you let them specify image transformations without limitations.

.. _DDoS: http://en.wikipedia.org/wiki/DDoS

Authenticate
^^^^^^^^^^^^

This event listener enforces the usage of signatures on all write requests against user-specific resources. You can read more about how the actual signature check works in the :ref:`signing-write-requests` topic in the :doc:`api` section.

To enforce the signature check for all write requests this event listener subscribes to the following events:

* ``image.put``
* ``image.post``
* ``image.delete``
* ``metadata.put``
* ``metadata.post``
* ``metadata.delete``

This event listener does not support any parameters and is enabled per default like this:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            'authenticate' => function() {
                return new EventListener\Authenticate();
            },
        ),

        // ...
    );

Disable this event listener with care. Clients can delete all your images and metadata when this listener is not enabled.

Auto rotate image
^^^^^^^^^^^^^^^^^

This event listener will auto rotate new images based on metadata embedded in the image itself (`EXIF`_).

.. _EXIF: http://en.wikipedia.org/wiki/Exchangeable_image_file_format

The listener does not support any parameters and can be enabled like this:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            'autoRotate' => function() {
                return new EventListener\AutoRotateImage();
            },
        ),

        // ...
    );

If you enable this listener all new images added to Imbo will be auto rotated based on the EXIF data.

CORS (Cross-Origin Resource Sharing)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

This event listener can be used to allow clients such as web browsers to use Imbo when the client is located on a different origin/domain than the Imbo server is. This is implemented by sending a set of CORS-headers on specific requests, if the origin of the request matches a configured domain.

The event listener can be configured on a per-resource and per-method basis, and will therefore listen to any related events. If enabled without any specific configuration, the listener will allow and respond to the **GET**, **HEAD** and **OPTIONS** methods on all resources. Note however that no origins are allowed by default and that a client will still need to provide a valid access token, unless the :ref:`access-token-event-listener` listener is disabled.

To enable the listener, use the following:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            'cors' => function() {
                return new EventListener\Cors(array(
                    'allowedOrigins' => array('http://some.origin'),
                    'allowedMethods' => array(
                        'image'  => array('GET', 'HEAD', 'PUT'),
                        'images' => array('GET', 'HEAD'),
                    ),
                    'maxAge' => 3600,
                ));
            },
        ),

        // ...
    );

``allowedOrigins`` is an array of allowed origins. Specifying ``*`` as a value in the array will allow any origin.

``allowedMethods`` is an associative array where the keys represent the resource (``image``, ``images``, ``metadata``, ``status`` and ``user``). The value is an array of HTTP methods you wish to open up.

``maxAge`` specifies how long the response of an OPTIONS-request can be cached for, in seconds. Defaults to 3600 (one hour).

Exif metadata
^^^^^^^^^^^^^

This event listener can be used to fetch the EXIF-tags from uploaded images and adding them as metadata. Enabling this event listener will not populate metadata for images already added to Imbo.

The event listener subscribes to the following events:

* ``image.put``
* ``db.image.insert``

and has the following parameters:

``$allowedTags``
    The tags you want to be populated as metadata, if present. Optional - by default all tags are added.

and is enabled like this:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            'exifMetadata' => function() {
                return new EventListener\ExifMetadata(array(
                    'exif:Make',
                    'exif:Model',
                ));
            },
        ),

        // ...
    );

which would allow only ``exif:Make`` and ``exif:Model`` as metadata tags. Not passing an array to the constructor will allow all tags.

Image transformation cache
^^^^^^^^^^^^^^^^^^^^^^^^^^

This event listener enables caching of image transformations. Read more about image transformations in the :ref:`image-transformations` topic in the :doc:`api` section.

To achieve this the listener subscribes to the following events:

* ``image.get`` (both before and after the main application logic)
* ``image.delete``

The event listener has one parameter:

``$path``
    Root path where the cached images will be stored.

and is enabled like this:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            'imageTransformationCache' => function() {
                return new EventListener\ImageTransformationCache('/path/to/cache');
            },
        ),

        // ...
    );

.. note::
    This event listener uses a similar algorithm when generating file names as the :ref:`filesystem-storage-driver` storage driver.

.. warning::
    It can be wise to purge old files from the cache from time to time. If you have a large amount of images and present many different variations of these the cache will use up quite a lot of storage.

    An example on how to accomplish this:

    .. code-block:: bash

        $ find /path/to/cache -ctime +7 -type f -delete

    The above command will delete all files in /path/to/cache older than 7 days and can be used with for instance `crontab`_.

.. _crontab: http://en.wikipedia.org/wiki/Cron

Max image size
^^^^^^^^^^^^^^

This event listener can be used to enforce a maximum size (height and width, not byte size) of **new** images. Enabling this event listener will not change images already added to Imbo.

The event listener subscribes to the following event:

* ``image.put``

and has the following parameters:

``$width``
    The max width in pixels of new images. If a new image exceeds this limit it will be downsized.

``$height``
    The max height in pixels of new images. If a new image exceeds this limit it will be downsized.

and is enabled like this:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            'maxImageSize' => function() {
                return new EventListener\MaxImageSize(1024, 768);
            },
        ),

        // ...
    );

which would effectively downsize all images exceeding a ``width`` of ``1024`` or a ``height`` of ``768``. The aspect ratio will be kept.

Metadata cache
^^^^^^^^^^^^^^

This event listener enables caching of metadata fetched from the backend so other requests won't need to go all the way to the backend to fetch metadata. To achieve this the listener subscribes to the following events:

* ``db.metadata.load``
* ``db.metadata.delete``
* ``db.metadata.update``

and has the following parameters:

``Imbo\Cache\CacheInterface $cache``
    An instance of a cache adapter. Imbo ships with :ref:`apc-cache` and :ref:`memcached-cache` adapters, and both can be used for this event listener. If you want to use another form of caching you can simply implement the ``Imbo\Cache\CacheInterface`` interface and pass an instance of the custom adapter to the constructor of the event listener. Here is an example that uses the APC adapter for caching:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            'metadataCache' => function() {
                return new EventListener\MetadataCache(new Cache\APC('imbo'));
            },
        ),

        // ...
    );

.. _stats-access:

Stats access
^^^^^^^^^^^^

This event listener controls the access to the :ref:`stats endpoint <stats-resource>` by using simple white-/blacklists containing IP addresses.

This listener is enabled per default, and only allows ``127.0.0.1`` to access the statistics:


.. code-block:: php

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            'statsAccess' => function() {
                return new EventListener\StatsAccess(array(
                    'whitelist' => array('127.0.0.1'),
                    'blacklist' => array(),
                ));
            },
        ),

        // ...
    );

If the whitelist is populated, only the listed IP addresses will gain access. If the blacklist is populated only the listed IP addresses will be denied access. If both lists are populated the IP address of the client must be present in the whitelist to gain access. If an IP address is present in both lists, it will not gain access.

The event object
++++++++++++++++

The object passed to the event listeners (and closures) is an instance of the ``Imbo\EventManager\EventInterface`` interface. This interface has some methods that event listeners can use:

``getName()``
    Get the name of the current event. For instance ``image.delete``.

``getRequest()``
    Get the current request object (an instance of ``Imbo\Http\Request\Request``)

``getResponse()``
    Get the current response object (an instance of ``Imbo\Http\Response\Response``)

``getDatabase()``
    Get the current database adapter (an instance of ``Imbo\Database\DatabaseInterface``)

``getStorage()``
    Get the current storage adapter (an instance of ``Imbo\Storage\StorageInterface``)

``getManager()``
    Get the current event manager (an instance of ``Imbo\EventManager\EventManager``)

.. _image-transformations:

Image transformations
---------------------

Imbo supports a set of image transformations out of the box using the `Imagick PHP extension <http://pecl.php.net/package/imagick>`_. All supported image transformations are included in the configuration, and you can easily add your own custom transformations or create presets using a combination of existing transformations.

Transformations are triggered using the ``t[]`` query parameter together with the image resource (read more about the image resource and the included transformations and their parameters in the :ref:`image-resource` section). This parameter should be used as an array so that multiple transformations can be made. The transformations are applied in the order they are specified in the URL.

All transformations are registered in the configuration array under the ``imageTransformations`` key:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'imageTransformations' => array(
            'border' => function (array $params) {
                return new Image\Transformation\Border($params);
            },
            'canvas' => function (array $params) {
                return new Image\Transformation\Canvas($params);
            },
            // ...
        ),

        // ...
    );

where the keys are the names of the transformations as specified in the URL, and the values are closures which all receive a single argument. This argument is an array that matches the parameters for the transformation as specified in the URL. If you use the following query parameter:

``t[]=border:width=1,height=2,color=f00``

the ``$params`` array given to the closure will look like this:

.. code-block:: php

    <?php
    array(
        'width' => '1',
        'height' => '1',
        'color' => 'f00'
    )


The return value of the closure must either be an instance of the ``Imbo\Image\Transformation\TransformationInterface`` interface, or code that is callable (for instance another closure, or a class that includes an ``__invoke`` method). If the return value is a callable piece of code it will receive a single parameter which is an instance of ``Imbo\Model\Image``, which is the image you want your transformation to modify. See some examples in the :ref:`custom-transformations` section below.

Presets
+++++++

Imbo supports the notion of transformation presets by using the ``Imbo\Image\Transformation\Collection`` transformation. The constructor of this transformation takes an array containing other transformations.

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'imageTransformations' => array(
            'graythumb' => function ($params) {
                return new Image\Transformation\Collection(array(
                    new Image\Transformation\Desaturate(),
                    new Image\Transformation\Thumbnail($params),
                ));
            },
        ),

        // ...
    );

which can be triggered using the following query parameter:

``t[]=graythumb``

.. _custom-transformations:

Custom transformations
++++++++++++++++++++++

You can also implement your own transformations by implementing the ``Imbo\Image\Transformation\TransformationInterface`` interface, or by specifying a callable piece of code. An implementation of the border transformation as a callable piece of code could for instance look like this:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'imageTransformations' => array(
            'border' => function (array $params) {
                return function (Model\Image $image) use ($params) {
                    $color = !empty($params['color']) ? $params['color'] : '#000';
                    $width = !empty($params['width']) ? $params['width'] : 1;
                    $height = !empty($params['height']) ? $params['height'] : 1;

                    try {
                        $imagick = new \Imagick();
                        $imagick->readImageBlob($image->getBlob());
                        $imagick->borderImage($color, $width, $height);

                        $size = $imagick->getImageGeometry();

                        $image->setBlob($imagick->getImageBlob())
                              ->setWidth($size['width'])
                              ->setHeight($size['height']);
                    } catch (\ImagickException $e) {
                        throw new Image\Transformation\TransformationException($e->getMessage(), 400, $e);
                    }
                };
            },
        ),

        // ...
    );

It's not recommended to use this method for big complicated transformations. It's better to implement the interface mentioned above, and refer to your class in the configuration array instead:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ..

        'imageTransformations' => array(
            'border' => function (array $params) {
                return new My\Custom\BorderTransformation($params);
            },
        ),

        // ...
    );

where ``My\Custom\BorderTransformation`` implements ``Imbo\Image\Transformation\TransformationInterface``.

Custom resources and routes
---------------------------

.. warning:: Custom resources and routes is an experimental and advanced way of extending Imbo, and requires extensive knowledge of how Imbo works internally.

If you need to create a custom route you can attach a route and a custom resource class using the configuration. Two keys exists for this purpose: ``routes`` and ``resources``:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'routes' => array(
            'users' => '#^/users(\.(?<extension>json|xml))?$#',
        ),

        'resources' => array(
            'users' => function() {
                return new Users();
            },

            // or

            'users' => __NAMESPACE__ . '\Users',
        ),

        // ...
    );

In the above example we are creating a route for Imbo using a regular expression, called ``users``. The route itself will match the following three requests:

* ``/users``
* ``/users.json``
* ``/users.xml``

When a request is made against any of these endpoints Imbo will try to access a resource that is specified with the same key (``users``). The value specified for this entry in the ``resources`` array must either be a string representing the name of the resource class or a closure that, when executed, returns an instance of the resource class. This resource class must implement at least two interfaces to be able to respond to a request: ``Imbo\Resource\ResourceInterface`` and ``Imbo\EventListener\ListenerInterface``.

Below is an example implementation of the ``Imbo\Users`` resource:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    use Imbo\Resource\ResourceInterface,
        Imbo\EventListener\ListenerInterface,
        Imbo\EventListener\ListenerDefinition,
        Imbo\EventManager\EventInterface,
        Imbo\Model\ListModel;

    class Users implements ResourceInterface, ListenerInterface {
        public function getAllowedMethods() {
            return array('GET');
        }

        public function getDefinition() {
            return array(
                new ListenerDefinition('users.get', array($this, 'get')),
            );
        }

        public function get(EventInterface $event) {
            $model = new ListModel();
            $model->setList('users', 'user', array_keys($event->getConfig()['auth']));
            $event->getResponse()->setModel($model);
        }
    }

This resource informs Imbo that it supports ``HTTP GET``, and specifies a callback for the ``users.get`` event. The name of the event is the name specified for the resource in the configuration above, along with the HTTP method, separated with a dot.

In the ``get()`` method we are simply creating a list model for Imbo's response formatter, and we are supplying the keys from the ``auth`` part of the configuration as data. When formatted as JSON the response looks like this:

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
