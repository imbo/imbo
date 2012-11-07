Configuration
=============

Imbo ships with a sample configuration file named ``config/config.php.dist`` that you must copy to ``config/config.php``. You will also have to update some configuration values in the ``config/config.php`` file for Imbo to work.

User key pairs
--------------

Every user that wishes to store images in Imbo needs a public and private key pair. These keys are stored in the ``auth`` part of the configuration file:

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

For the private keys you can for instance use a `SHA-256`_ hash of a random value. The private key is used by clients to sign requests, and if you accidentally give away your private key users can use it to delete all your images. Make sure not to generate a private key that is easy to guess (like for instance the MD5 or SHA-256 hash of the public key).

Imbo ships with a small command line tool that can be used to generate private keys for you using the `openssl_random_pseudo_bytes`_ function. The script is located in the `scripts` directory and does not require any arguments:

.. code-block:: bash

    $ php scripts/generatePrivateKey.php
    3b98dde5f67989a878b8b268d82f81f0858d4f1954597cc713ae161cdffcc84a

.. _SHA-256: http://en.wikipedia.org/wiki/SHA-2
.. _openssl_random_pseudo_bytes: http://php.net/openssl_random_pseudo_bytes

The private key can be changed whenever you want as long as you remember to change it in both the server configuration and in the client you use. The public key can not be changed easily as database and storage drivers use it when storing images and metadata.

Database configuration
----------------------

The database driver you decide to use is responsible for storing metadata and basic image information, like width and height for example. Imbo ships with some different implementations that you can use. Remember that you will not be able to switch the driver whenever you want and expect all data to be automatically transferred. Choosing a database driver should be a long term commitment unless you have migration scripts available.

In the sample configuration file the :ref:`default-database-driver` storage driver is used. Which database driver to use is specified in the ``driver`` part of the ``database`` array in the configuration file. The driver can be specified in two different ways:

1) By specifying the fully qualified class name of the database driver to use, along with parameters that will be passed to the constructor of the driver:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'database' => array(
            'driver' => 'Imbo\Database\MongoDB',
            'params' => array(
                'databaseName'   => 'imbo',
                'collectionName' => 'images',
            ),
        ),

        // ...
    );

2) or by specifying an instance of a driver:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'database' => array(
            'driver' => new Database\MongoDB(array(
                'databaseName'   => 'imbo',
                'collectionName' => 'images',
            )),
        ),

        // ...
    );

By using the former method Imbo will not instantiate the driver before it is needed.

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

When using this driver you need to create a couple of tables in the `DBMS`_ you choose to use. Below you will find statements to create the necessary tables in `SQLite`_:

.. _DBMS: http://en.wikipedia.org/wiki/Relational_database_management_system
.. _SQLite: http://www.sqlite.org/

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

If you wish to use some other DBMS, like for instance `MySQL`_ or `PostgreSQL`_ you will have to make some small changes to the statements above.

.. note:: Imbo will not create these tables automatically.

.. _MySQL: http://www.mysql.com/
.. _PostgreSQL: http://www.postgresql.org/

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

        'database' => array(
            'driver' => 'Imbo\Database\Doctrine',
            'params' => array(
                'pdo' => new \PDO('sqlite:/path/to/database'),
            ),
        ),

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

        'database' => array(
            'driver' => 'Imbo\Database\Doctrine',
            'params' => array(
                'dbname'   => 'database',
                'user'     => 'username',
                'password' => 'password',
                'host'     => 'hostname',
                'driver'   => 'pdo_mysql',
            ),
        ),

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

``collectionName``
    Name of the collection to use. Defaults to ``images``.

``server``
    The server string to use when connecting. Defaults to ``mongodb://localhost:27017``.

``options``
    Options passed to the underlying driver. Defaults to ``array('connect' => true, 'timeout' => 1000)``. See the `manual for the Mongo constructor`_ at `php.net <http://php.net>`_ for available options.

``slaveOk``
    Whether or not reads should be sent to secondary members of a replica set for all possible queries. Defaults to ``false``.

.. _manual for the Mongo constructor: http://php.net/manual/en/mongo.construct.php

Examples
~~~~~~~~

1) Connect to a local MongoDB instance using the default ``databaseName`` and ``collectionName``:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'database' => array(
            'driver' => 'Imbo\Database\MongoDB',
        ),

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

        'database' => array(
            'driver' => 'Imbo\Database\MongoDB',
            'params' => array(
                'server'         => 'mongodb://server1,server2,server3',
                'replicaSet'     => 'nameOfReplicaSet',
                'slaveOk'        => true,
            ),
        ),

        // ...
    );

Storage configuration
---------------------

Storage drivers are responsible for storing the original images you put into imbo. Like with the database driver it is not possible to simply switch a driver without having migration scripts available to move the stored images. Choose a driver with care.

In the sample configuration file the :ref:`default-storage-driver` storage driver is used. Which storage driver to use is specified in the ``driver`` part of the ``storage`` array in the configuration file. The driver can be specified in two different ways:

1) By specifying the fully qualified class name of the storage driver to use, along with parameters that will be passed to the constructor of the driver:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'storage' => array(
            'driver' => 'Imbo\Storage\Filesystem',
            'params' => array(
                'dataDir' => '/path/to/images',
            ),
        ),

        // ...
    );

2) or by specifying an instance of a driver:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'storage' => array(
            'driver' => new Storage\Filesystem(array(
                'dataDir' => '/path/to/images',
            )),
        ),

        // ...
    );

By using the former method Imbo will not instantiate the driver before it is needed.

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

When using this driver you need to create a table in the `DBMS`_ you choose to use. This table will hold your image data. Below you will find a statement to create this table in `SQLite`_:

.. _DBMS: http://en.wikipedia.org/wiki/Relational_database_management_system
.. _SQLite: http://www.sqlite.org/

.. code-block:: sql
    :linenos:

    CREATE TABLE storage_images (
        publicKey TEXT NOT NULL,
        imageIdentifier TEXT NOT NULL,
        data BLOB NOT NULL,
        created INTEGER NOT NULL,
        PRIMARY KEY (publicKey,imageIdentifier)
    )

If you wish to use some other DBMS, like for instance `MySQL`_ or `PostgreSQL`_ you will have to make some small changes to the statement above.

.. note:: Imbo will not create the table automatically.

.. _MySQL: http://www.mysql.com/
.. _PostgreSQL: http://www.postgresql.org/

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

        'storage' => array(
            'driver' => 'Imbo\Storage\Doctrine',
            'params' => array(
                'pdo' => new \PDO('sqlite:/path/to/database'),
            ),
        ),

        // ...
    );

2) Connect to a MySQL database using PDO:

.. _PDO: http://php.net/pdo

.. code-block:: php

    <?php
    namespace Imbo;

    return array(
        // ...

        'storage' => array(
            'driver' => 'Imbo\Storage\Doctrine',
            'params' => array(
                'dbname'   => 'database',
                'user'     => 'username',
                'password' => 'password',
                'host'     => 'hostname',
                'driver'   => 'pdo_mysql',
            ),
        ),

        // ...
    );

.. _filesystem-storage-driver:
.. _default-storage-driver:

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

        'storage' => array(
            'driver' => 'Imbo\Storage\Filesystem',
            'params' => array(
                'dataDir' => '/path/to/images',
            ),
        ),

        // ...
    );

.. _gridfs-storage-driver:

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

``slaveOk``
    Whether or not reads should be sent to secondary members of a replica set for all possible queries. Defaults to ``false``.

Examples
~~~~~~~~

1) Connect to a local MongoDB instance using the default ``databaseName``:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'storage' => array(
            'driver' => 'Imbo\Storage\GridFS',
        ),

        // ...
    );

2) Connect to a `replica set`_:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'storage' => array(
            'driver' => 'Imbo\Storage\GridFS',
            'params' => array(
                'server'         => 'mongodb://server1,server2,server3',
                'replicaSet'     => 'nameOfReplicaSet',
                'slaveOk'        => true,
            ),
        ),

        // ...
    );

.. _configuration-event-listeners:

Event listeners
---------------

Imbo also supports event listeners that you can use to hook into Imbo at different phases without having to edit Imbo itself. An event listener is simply a piece of code that will be executed when a certain event is triggered from Imbo. Event listeners are added to the ``eventListeners`` part of the configuration array and can be added in two ways:

1) Use an instance of a class implementing the ``Imbo\EventListener\ListenerInterface`` interface:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            array(
                'listener' => new EventListener\AccessToken(),
            ),
        ),

        // ...
    );

2) Use a `closure`_:

.. _closure: http://php.net/manual/en/functions.anonymous.php

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            array(
                'listener' => function(EventManager\EventInterface $event) {
                    // Custom code
                },
                'events' => array(
                    'image.get.pre',
                    'image.get.post',
                ),
            ),
        ),

        // ...
    );

where ``listener`` is the code you want executed, and ``events`` is an array of the events you want it triggered for.

Per default an event listener is executed for all public keys (users). If you want a listener to only trigger for a specific public key you can specify this in the configuration:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            array(
                'listener' => new EventListener\AccessToken(),
                'publicKeys' => array('someUser', 'andAnotherUser'),
            ),
            array(
                'listener' => function(EventManager\EventInterface $event) {
                    // Custom code
                },
                'publicKeys' => array('username', 'anotherUsername'),
                'events' => array(
                    'image.get.pre',
                    'image.get.post',
                ),
            ),
        ),

        // ...
    );

where ``publicKeys`` is an array containing the public keys you want the listener to trigger for. You can read more about creating your own event listeners in the :ref:`custom-event-listeners` section.

Events
++++++

When configuring an event listener you need to know about the events that Imbo triggers. The most important events are combinations of the accessed resource along with the HTTP method used. Imbo currently provides five resources:

* :ref:`status <status-resource>`
* :ref:`user <user-resource>`
* :ref:`images <images-resource>`
* :ref:`image <image-resource>`
* :ref:`metadata <metadata-resource>`

Imbo will trigger an event **before** the main resource logic kicks in per HTTP method, and also **after**. Examples of events that is triggered:

* ``image.get.pre``
* ``image.put.pre``
* ``image.delete.post``

As you can see from the above examples the events are built up by the resource name, the HTTP method and a keyword that can be ``pre`` or ``post``, separated by ``.``.

Below you will see the different event listeners that Imbo ships with and the events they listen for.

Event listeners
+++++++++++++++

Imbo ships with a collection of event listeners for you to use. Two of them are enabled in the sample configuration file.

.. contents::
    :local:
    :depth: 1

Access token
^^^^^^^^^^^^

This event listener enforces the usage of access tokens on all requests against user-specific resources. You can read more about how the actual access tokens works in the :ref:`access-tokens` topic in the :doc:`api` section.

To enforce the access token check for all read requests this event listener listens for these events:

* ``user.get.pre``
* ``images.get.pre``
* ``image.get.pre``
* ``metadata.get.pre``
* ``user.head.pre``
* ``images.head.pre``
* ``image.head.pre``
* ``metadata.head.pre``

This event listener has a single parameter that can be used to whitelist and/or blacklist certain image transformations, used when the current request is against an image resource. The parameter is an array with a single key: ``transformations``. This is another array with two keys: ``whitelist`` and ``blacklist``. These two values are arrays where you specify which transformation(s) to whitelist or blacklist. The names of the transformations are the same as the ones used in the request. See :ref:`image-transformations` for a complete list of the supported transformations.

Use ``whitelist`` if you want the listener to skip the access token check for certain transformations, and ``blacklist`` if you want it to only check certain transformations:

.. code-block:: php

    array('transformations' => array(
        'whitelist' => array(
            'convert',
        )
    ))

means that the access token will **not** be enforced for the :ref:`convert-transformation` transformation.

.. code-block:: php

    array('transformations' => array(
        'blacklist' => array(
            'convert',
        )
    ))

means that the access token will be enforced **only** for the :ref:`convert-transformation` transformation.

If both ``whitelist`` and ``blacklist`` are specified all transformations will require an access token unless it's included in ``whitelist``.

This event listener is included in the default configuration file without specifying any filters (which means that the access token will be enforced for all requests):

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            array(
                'listener' => new EventListener\AccessToken(),
            ),
        ),

        // ...
    );

Authenticate
^^^^^^^^^^^^

This event listener enforces the usage of signatures on all write requests against user-specific resources. You can read more about how the actual signature check works in the :ref:`signing-write-requests` topic in the :doc:`api` section.

To enforce the signature check for all write requests this event listener listens for these events:

* ``image.put.pre``
* ``image.post.pre``
* ``image.delete.pre``
* ``metadata.put.pre``
* ``metadata.post.pre``
* ``metadata.delete.pre``

This event listener does not support any parameters and is enabled per default like this:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'eventListeners' => array(
            array(
                'listener' => new EventListener\Authenticate(),
            ),
        ),

        // ...
    );

Image transformation cache
^^^^^^^^^^^^^^^^^^^^^^^^^^

This event listener enables caching of image transformations. Read more about image transformations in the :ref:`image-transformations` topic in the :doc:`api` section.

To achieve this the listener listens for the following events:

* ``image.get.pre``
* ``image.get.post``
* ``image.delete.post``

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
            array(
                'listener' => new EventListener\ImageTransformationCache('/path/to/cache'),
            ),
        ),

        // ...
    );

.. note::
    This event listener uses a similar algorithm when generating file names as the :ref:`filesystem-storage-driver` storage driver.

.. warning::
    It can be wise to purge old files from the cache from time to time. If you have a large amount of images and present many different variations of these the cache will use up quite a lot of storage.

    An example on how to accomplish this:

    .. code-block:: bash

        find /path/to/cache -ctime +7 -type f -delete

    The above command will delete all files in /path/to/cache older than 7 days and can be used with for instance `crontab`_.

.. _crontab: http://en.wikipedia.org/wiki/Cron

Max image size
^^^^^^^^^^^^^^

This event listener can be used to enforce a maximum size (height and width, not byte size) of **new** images. Enabling this event listener will not change images already added to Imbo.

The event listener listens to a special event:

* ``image.put.imagepreparation.post``

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
            array(
                'listener' => new EventListener\MaxImageSize(1024, 768),
            ),
        ),

        // ...
    );

which would effectively downsize all images exceeding a ``width`` of ``1024`` or a ``height`` of ``768``.

The event this listener listens for is special in the case that the image resource has executed parts of its logic, and "prepared" the internal image instance available via the ``$event`` object passed to the listener. If the listener would listen for ``image.put.pre`` the ``$event`` object would not yet have an image instance to work with.

The event object
++++++++++++++++

The object passed to the event listeners (and closures) is an instance of the ``Imbo\EventManager\EventInterface`` interface. This interface has two methods that event listeners can use:

``getName()``
    Get the name of the current event. For instance ``image.delete.post``.

``getContainer()``
    Get the dependency injection container. This container can be used to fetch the current request and response objects for instance.

Have a look at how the event listeners shipped with Imbo have been implemented with regards to fetching the request and response objects.

.. _image-transformations:

Image transformations
---------------------

Imbo supports a set of image transformations out of the box using the `Imagick PHP extension <http://pecl.php.net/package/imagick>`_. All supported image transformations are included in the configuration, and you can easily add your own custom transformations or create presets using a combination of existing transformations.

Transformations are triggered using the ``t[]`` query parameter together with the image resource (read more about the image resource and the included transformations and their parameters in the :ref:`image-resource` section). This parameter should be used as an array so that multiple transformations can be made. The transformations are applied in the order they are specified in the URL.

All transformations are registered in the configuration array under the ``transformations`` key:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'transformations' => array(
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


The return value of the closure must either be an instance of the ``Imbo\Image\Transformation\TransformationInterface`` interface, or code that is callable (for instance another closure, or a class that includes an ``__invoke`` method). If the return value is a callable piece of code it will receive a single parameter which is an instance of ``Imbo\Image\ImageInterface`` which is the image you want your transformation to modify. See some examples in the :ref:`custom-transformations` section below.

Presets
+++++++

Imbo supports the notion of transformation presets by using the ``Imbo\Image\Transformation\Collection`` transformation. The constructor of this transformation takes an array containing other transformations.

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ...

        'transformations' => array(
            'graythumb' => function ($params) {
                return new Image\Transformation\Collection(array(
                    new Image\Transformation\Thumbnail($params),
                    new Image\Transformation\Desaturate(),
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

        'transformations' => array(
            'border' => function (array $params) {
                return function (Image\ImageInterface $image) use ($params) {
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

It's not recommended to use this method for big complicated transformations. It's better to implement the interface mentioned above, and refer to that class in the configuration array instead:

.. code-block:: php
    :linenos:

    <?php
    namespace Imbo;

    return array(
        // ..

        'transformations' => array(
            'border' => function (array $params) {
                return new My\Custom\BorderTransformation($params);
            },
        ),

        // ...
    );

where ``My\Custom\BorderTransformation`` implements ``Imbo\Image\Transformation\TransformationInterface``.

Varnish
-------

Imbo strives to follow the `HTTP Protocol`_, and can because of this easily leverage `Varnish`_.

.. _HTTP Protocol: http://www.ietf.org/rfc/rfc2616.txt
.. _Varnish: https://www.varnish-cache.org/

The only required configuration you need in your `VCL`_ is a default backend:

.. _VCL: https://www.varnish-cache.org/docs/3.0/reference/vcl.html

.. code-block:: console

    backend default {
        .host = "127.0.0.1";
        .port = "81";
    }

where ``.host`` and ``.port`` is where Varnish can reach your web server.

If you use the same host name (or a sub-domain) for your Imbo installation as other services, that in turn uses `Cookies`_, you might want the VCL to ignore these Cookies for the requests made against your Imbo installation (unless you have implemented event listeners for Imbo that uses Cookies). To achieve this you can put the following snippet into your VCL file:

.. _Cookies: http://en.wikipedia.org/wiki/HTTP_cookie

.. code-block:: console

    sub vcl_recv {
        if (req.http.host == "imbo.example.com") {
            unset req.http.Cookie;
        }
    }

or, if you have Imbo installed in some path:

.. code-block:: console

    sub vcl_recv {
        if (req.http.host ~ "^(www.)?example.com$" && req.url ~ "^/imbo/") {
            unset req.http.Cookie;
        }
    }

if you have Imbo installed in ``example.com/imbo``.

