.. _configuration:

Configuration
=============

Imbo ships with a default configuration file that it will load. You will have to create a configuration file of your own that will automatically be loaded and merged with the default configuration by Imbo. The location of this file depends on the :ref:`installation method <installation>` you choose. You should never have to update the default configuration file provided by Imbo.

The configuration file you need to create should simply return an array with configuration data. All available configuration options is covered in this chapter.

.. contents::
    :local:
    :depth: 1

Imbo users - ``auth``
---------------------

Every user that wants to store images in Imbo needs a public and private key pair. These keys are stored in the ``auth`` part of your configuration file:

.. code-block:: php

    <?php
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

For the private keys you can for instance use a `SHA-256 <http://en.wikipedia.org/wiki/SHA-2>`_ hash of a random value. The private key is used by clients to sign requests, and if you accidentally give away your private key users can use it to delete all your images. Make sure not to generate a private key that is easy to guess (like for instance the MD5 or SHA-256 hash of the public key). Imbo does not require the private key to be in a specific format, so you can also use regular passwords if you want. The key itself will never be a part of the payload sent to the server.

Imbo ships with a small command line tool that can be used to generate private keys for you using the `openssl_random_pseudo_bytes <http://php.net/openssl_random_pseudo_bytes>`_ function. The script is located in the ``scripts`` directory of the Imbo installation and does not require any arguments:

.. code-block:: bash

    $ php scripts/generatePrivateKey.php
    3b98dde5f67989a878b8b268d82f81f0858d4f1954597cc713ae161cdffcc84a

The private key can be changed whenever you want as long as you remember to change it in both the server configuration and in the client you use. The public key can not be changed easily as database and storage adapter use it when storing images and metadata.

.. _database-configuration:

Database configuration - ``database``
-------------------------------------

The database adapter you decide to use is responsible for storing metadata and basic image information, like width and height for example, along with the generated short URLs. Imbo ships with some different implementations that you can use. Remember that you will not be able to switch the adapter whenever you want and expect all data to be automatically transferred. Choosing a database adapter should be a long term commitment unless you have migration scripts available.

In the default configuration file the :ref:`default-database-adapter` database adapter is used. You can choose to override this in your ``config.php`` file by specifying a different adapter. You can either specify an instance of a database adapter directly, or specify an anonymous function that will return an instance of a database adapter when executed. Which database adapter to use is specified in the ``database`` key in the configuration array:

.. code-block:: php

    <?php
    return array(
        // ...

        'database' => function() {
            return new Imbo\Database\MongoDB(array(
                'databaseName' => 'imbo',
            ));
        },

        // or

        'database' => new Imbo\Database\MongoDB(array(
            'databaseName' => 'imbo',
        )),

        // ...
    );

.. _doctrine-database-adapter:

Doctrine
++++++++

This adapter uses the `Doctrine Database Abstraction Layer <http://www.doctrine-project.org/projects/dbal.html>`_. The options you pass to the constructor of this adapter is passed to the underlying classes, so have a look at the Doctrine DBAL documentation over at `doctrine-project.org <http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/index.html>`_. When using this adapter you need to create the required tables in the DBMS first, as specified in the :ref:`installation` chapter.

Examples
^^^^^^^^

Here are some examples on how to use the Doctrine adapter in the configuration file:

1) Use a `PDO <http://php.net/pdo,>`_ instance to connect to a SQLite database:

.. code-block:: php

    <?php
    return array(
        // ...

        'database' => function() {
            return new Imbo\Database\Doctrine(array(
                'pdo' => new PDO('sqlite:/path/to/database'),
            ));
        },

        // ...
    );

2) Connect to a MySQL database using PDO:

.. code-block:: php

    <?php
    return array(
        // ...

        'database' => function() {
            return new Imbo\Database\Doctrine(array(
                'dbname'   => 'database',
                'user'     => 'username',
                'password' => 'password',
                'host'     => 'hostname',
                'driver'   => 'pdo_mysql',
            ));
        },

        // ...
    );

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
    Options passed to the underlying adapter. Defaults to ``array('connect' => true, 'timeout' => 1000)``. See the `manual for the Mongo constructor <http://php.net/manual/en/mongo.construct.php>`_ for available options.

Examples
^^^^^^^^

1) Connect to a local MongoDB instance using the default ``databaseName``:

.. code-block:: php

    <?php
    return array(
        // ...

        'database' => function() {
            return new Imbo\Database\MongoDB();
        },

        // ...
    );

2) Connect to a `replica set <http://www.mongodb.org/display/DOCS/Replica+Sets>`_:

.. code-block:: php

    <?php
    return array(
        // ...

        'database' => function() {
            return new Imbo\Database\MongoDB(array(
                'server' => 'mongodb://server1,server2,server3',
                'options' => array(
                    'replicaSet' => 'nameOfReplicaSet',
                ),
            ));
        },

        // ...
    );

Custom database adapter
+++++++++++++++++++++++

If you need to create your own database adapter you need to create a class that implements the ``Imbo\Database\DatabaseInterface`` interface, and then specify that adapter in the configuration:

.. code-block:: php

    <?php
    return array(
        // ...

        'database' => function() {
            return new My\Custom\Adapter(array(
                'some' => 'option',
            ));
        },

        // ...
    );

More about how to achieve this in the :doc:`../develop/custom_adapters` chapter.

.. _storage-configuration:

Storage configuration - ``storage``
-----------------------------------

Storage adapters are responsible for storing the original images you put into Imbo. As with the database adapter it is not possible to simply switch the adapter without having migration scripts available to move the stored images. Choose an adapter with care.

In the default configuration file the :ref:`default-storage-adapter` storage adapter is used. You can choose to override this in your ``config.php`` file by specifying a different adapter. You can either specify an instance of a storage adapter directly, or specify an anonymous function that will return an instance of a storage adapter when executed. Which storage adapter to use is specified in the ``storage`` key in the configuration array:

.. code-block:: php

    <?php
    return array(
        // ...

        'storage' => function() {
            return new Imbo\Storage\Filesystem(array(
                'dataDir' => '/path/to/images',
            ));
        },

        // or

        'storage' => new Imbo\Storage\Filesystem(array(
            'dataDir' => '/path/to/images',
        )),

        // ...
    );

Doctrine
++++++++

This adapter uses the `Doctrine Database Abstraction Layer <http://www.doctrine-project.org/projects/dbal.html>`_. The options you pass to the constructor of this adapter is passed to the underlying classes, so have a look at the Doctrine DBAL documentation over at `doctrine-project.org <http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/index.html>`_. When using this adapter you need to create the required tables in the DBMS first, as specified in the :ref:`installation` chapter.

Examples
^^^^^^^^

Here are some examples on how to use the Doctrine adapter in the configuration file:

1) Use a PDO instance to connect to a SQLite database:

.. code-block:: php

    <?php
    return array(
        // ...

        'storage' => function() {
            return new Imbo\Storage\Doctrine(array(
                'pdo' => new PDO('sqlite:/path/to/database'),
            ));
        },

        // ...
    );

2) Connect to a MySQL database using PDO:

.. code-block:: php

    <?php
    return array(
        // ...

        'storage' => function() {
            return new Imbo\Storage\Doctrine(array(
                'dbname'   => 'database',
                'user'     => 'username',
                'password' => 'password',
                'host'     => 'hostname',
                'driver'   => 'pdo_mysql',
            ));
        },

        // ...
    );

.. _filesystem-storage-adapter:

Filesystem
++++++++++

This adapter simply stores all images on the file system. It only has a single parameter, and that is the base directory of where you want your images stored:

``dataDir``
    The base path where the images are stored.

This adapter is configured to create subdirectories inside of ``dataDir`` based on the public key of the user and the checksum of the images added to Imbo. The algorithm that generates the path simply takes the three first characters of the public key and creates directories for each of them, then the full public key, then a directory of each of the first characters in the image identifier, and lastly it stores the image in a file with a filename equal to the image identifier itself.

Examples
^^^^^^^^

Default configuration:

.. code-block:: php

    <?php
    return array(
        // ...

        'storage' => function() {
            new Imbo\Storage\Filesystem(array(
                'dataDir' => '/path/to/images',
            ));
        },

        // ...
    );

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
    Options passed to the underlying adapter. Defaults to ``array('connect' => true, 'timeout' => 1000)``. See the `manual for the Mongo constructor <http://php.net/manual/en/mongo.construct.php>`_ for available options.

Examples
^^^^^^^^

1) Connect to a local MongoDB instance using the default ``databaseName``:

.. code-block:: php

    <?php
    return array(
        // ...

        'storage' => function() {
            return new Imbo\Storage\GridFS();
        },

        // ...
    );

2) Connect to a replica set:

.. code-block:: php

    <?php
    return array(
        // ...

        'storage' => function() {
            return new Imbo\Storage\GridFS(array(
                'server' => 'mongodb://server1,server2,server3',
                'options' => array(
                    'replicaSet' => 'nameOfReplicaSet',
                ),
            ));
        },

        // ...
    );

Custom storage adapter
++++++++++++++++++++++

If you need to create your own storage adapter you need to create a class that implements the ``Imbo\Storage\StorageInterface`` interface, and then specify that adapter in the configuration:

.. code-block:: php

    <?php
    return array(
        // ...

        'storage' => function() {
            return new My\Custom\Adapter(array(
                'some' => 'option',
            ));
        },

        // ...
    );

More about how to achieve this in the :doc:`../develop/custom_adapters` chapter.

.. _configuration-event-listeners:

Event listeners - ``eventListeners``
------------------------------------

Imbo also supports event listeners that you can use to hook into Imbo at different phases without having to edit Imbo itself. An event listener is simply a piece of code that will be executed when a certain event is triggered from Imbo. Event listeners are added to the ``eventListeners`` part of the configuration array as associative arrays. The keys are short names used to identify the listeners, and are not really used for anything in the Imbo application, but exists so you can override/disable event listeners specified in the default configuration. If you want to disable some of the default event listeners simply specify the same key in your configuration file and set the value to ``null`` or ``false``.

Event listeners can be added in the following ways:

1) Use an instance of a class implementing the ``Imbo\EventListener\ListenerInterface`` interface:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'accessToken' => new Imbo\EventListener\AccessToken(),
        ),

        // ...
    );

2) An anonymous function returning an instance of a class implementing the ``Imbo\EventListener\ListenerInterface`` interface:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'accessToken' => function() {
                return new Imbo\EventListener\AccessToken();
            },
        ),

        // ...
    );

3) Use an instance of a class implementing the ``Imbo\EventListener\ListenerInterface`` interface together with a public key filter:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'maxImageSize' => array(
                'listener' => new Imbo\EventListener\MaxImageSize(1024, 768),
                'publicKeys' => array(
                    'include' => array('user'),
                    // 'exclude' => array('someotheruser'),
                ),
            ),
        ),

        // ...
    );

where ``listener`` is an instance of a class implementing the ``Imbo\EventListener\ListenerInterface`` interface, and ``publicKeys`` is an array that you can use if you want your listener to only be triggered for some users (public keys). The value of this is an array with one of two keys: ``include`` or ``exclude`` where ``include`` is an array you want your listener to trigger for, and ``exclude`` is an array of users you don't want your listener to trigger for. ``publicKeys`` is optional, and per default the listener will trigger for all users.

4) Use an anonymous function directly:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'customListener' => array(
                'callback' => function(Imbo\EventManager\EventInterface $event) {
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

where ``callback`` is the code you want executed, and ``events`` is an array of the events you want it triggered for. ``priority`` is the priority of the listener and defaults to 1. The higher the number, the earlier in the chain your listener will be triggered. This number can also be negative. Imbo's internal event listeners uses numbers between 1 and 100. ``publicKeys`` uses the same format as described above. This way of attaching event listeners should mostly be used for quick and temporary solutions.

All event listeners will end up receiving an event object (which implements ``Imbo\EventManager\EventInterface``), that is described in detail in the :ref:`the-event-object` section.

Listeners added by default
++++++++++++++++++++++++++

The default configuration file includes some event listeners by default:

* :ref:`access-token-event-listener`
* :ref:`authenticate-event-listener`
* :ref:`stats-access-event-listener`

Read more about these listeners in the :doc:`../develop/event_listeners` chapter. If you want to disable any of these you could do so in your configuration file in the following way:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'accessToken' => null,
            'auth' => null,
            'statsAccess' => null,
        ),

        // ...
    );

Keep in mind that these listeners are added by default for a reason, so make sure you know what it means to remove them before you do so.

.. _image-transformations-config:

Image transformations - ``imageTransformations``
------------------------------------------------

Imbo supports a set of image transformations out of the box using the `Imagick PHP extension <http://pecl.php.net/package/imagick>`_. All supported image transformations are included in the configuration, and you can easily add your own custom transformations or create presets using a combination of existing transformations.

Transformations are triggered using the ``t`` query parameter together with the image resource (read more about the image resource and the included transformations and their parameters in the :ref:`image-resource` section). This query parameter is used as an array so that multiple transformations can be applied. The transformations are applied in the order they are specified in the URL.

All transformations are registered in the configuration array under the ``imageTransformations`` key:

.. code-block:: php

    <?php
    return array(
        // ...

        'imageTransformations' => array(
            'border' => function (array $params) {
                return new Imbo\Image\Transformation\Border($params);
            },
            'canvas' => function (array $params) {
                return new Imbo\Image\Transformation\Canvas($params);
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


The return value of the closure must either be an instance of the ``Imbo\Image\Transformation\TransformationInterface`` interface, or code that is callable (for instance another anonymous function, or a class that implements an ``__invoke`` method). If the return value is a callable piece of code it will receive a single parameter which is an instance of ``Imbo\Model\Image``, which is the image you want your transformation to modify.

Presets
+++++++

Imbo supports transformation presets by using the ``Imbo\Image\Transformation\Collection`` transformation. The constructor of this transformation takes an array containing other transformations.

.. code-block:: php

    <?php
    return array(
        // ...

        'imageTransformations' => array(
            'graythumb' => function ($params) {
                return new Imbo\Image\Transformation\Collection(array(
                    new Imbo\Image\Transformation\Desaturate(),
                    new Imbo\Image\Transformation\Thumbnail($params),
                ));
            },
        ),

        // ...
    );

which can be triggered using the following query parameter:

``t[]=graythumb``

If you want to implement your own set of image transformation you can see how in the :doc:`../develop/image_transformations` chapter.

Custom resources and routes - ``resources`` and ``routes``
----------------------------------------------------------

.. warning:: Custom resources and routes is an experimental and advanced way of extending Imbo, and requires extensive knowledge of how Imbo works internally.

If you need to create a custom route you can attach a route and a custom resource class using the configuration. Two keys exists for this purpose: ``resources`` and ``routes``:

.. code-block:: php

    <?php
    return array(
        // ...

        'resources' => array(
            'users' => new ImboUsers();

            // or

            'users' => function() {
                return new ImboUsers();
            },

            // or

            'users' => 'ImboUsers',
        ),

        'routes' => array(
            'users' => '#^/users(\.(?<extension>json|xml))?$#',
        ),

        // ...
    );

In the above example we are creating a route for Imbo using a regular expression, called ``users``. The route itself will match the following three requests:

* ``/users``
* ``/users.json``
* ``/users.xml``

When a request is made against any of these endpoints Imbo will try to access a resource that is specified with the same key (``users``). The value specified for this entry in the ``resources`` array can be:

1) a string representing the name of the resource class
2) an instance of a resource class
3) an anonymous function that, when executed, returns an instance of a resource class

The resource class specified in 2. and returned by 3. must implement the ``Imbo\Resource\ResourceInterface`` interface to be able to response to a request.

Below is an example implementation of the ``ImboUsers`` resource used in the above configuration:

.. code-block:: php

    <?php
    use Imbo\Resource\ResourceInterface,
        Imbo\EventListener\ListenerDefinition,
        Imbo\EventManager\EventInterface,
        Imbo\Model\ListModel;

    class ImboUsers implements ResourceInterface {
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
