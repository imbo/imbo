.. _configuration:

Configuration
=============

Imbo ships with a default configuration file that will be automatically loaded. You will have to create one or more configuration files of your own that will be automatically merged with the default configuration by Imbo. The location of these files depends on the :ref:`installation method <installation>` you choose. You should never have to edit the default configuration file provided by Imbo.

The configuration file(s) you need to create should simply return arrays with configuration data. All available configuration options are covered in this chapter.

.. contents::
    :local:
    :depth: 1

Imbo users - ``auth``
---------------------

Every user that wants to store images in Imbo needs a public and one or more private key. Imbo supports both read+write and read-only private keys. These keys can either be stored in the configuration file, or they can be fetched using a custom adapter. The configuration is done in the ``auth`` part of your configuration file:

.. code-block:: php

    <?php
    return [
        // ...

        'auth' => [
            // Read+write private key:
            'username'  => '95f02d701b8dc19ee7d3710c477fd5f4633cec32087f562264e4975659029af7',

            // Or, specify individual read-only and read+write keys:
            'otheruser' => [
                'ro' => 'b312ff29d5da23dcd230b61ff4db1e2515c862b9fb0bb59e7dd54ce1e4e94a53',
                'rw' => 'd5da23dcd2e2515c862b9fb0bb59e7dd54cb312ff29d594a53b11b8dc87f5622',
            ],

            // There is also support for multiple private keys:
            'someuser' => [
                'ro' => ['multiple', 'different', 'keys'],
                'rw' => ['different', 'read+write'],
            ],
        ],

        // ...
    ];

The public keys can consist of the following characters:

* a-z (only lowercase is allowed)
* 0-9
* _ and -

and must be at least 3 characters long.

For the private keys you can for instance use a `SHA-256 <http://en.wikipedia.org/wiki/SHA-2>`_ hash of a random value. The private key is used by clients to sign requests, and if you accidentally give away your private key users can use it to delete all your images (given it's a read+write key). Make sure not to generate a private key that is easy to guess (like for instance the MD5 or SHA-256 hash of the public key). Imbo does not require the private key to be in a specific format, so you can also use regular passwords if you want. The key itself will never be a part of the payload sent to/from the server.

Imbo ships with a small command line tool that can be used to generate private keys for you using the `openssl_random_pseudo_bytes <http://php.net/openssl_random_pseudo_bytes>`_ function. The script is located in the ``scripts`` directory of the Imbo installation and does not require any arguments:

.. code-block:: bash

    $ php scripts/generatePrivateKey.php
    3b98dde5f67989a878b8b268d82f81f0858d4f1954597cc713ae161cdffcc84a

The private key can be changed whenever you want as long as you remember to change it in both the server configuration and in the client you use. The user can not be changed easily as database and storage adapters use it when storing/fetching images and metadata.

Custom user lookup adapter
++++++++++++++++++++++++++

You can also use a custom adapter to fetch the public and private keys. The adapter must implement the ``Imbo\Auth\UserLookupInterface``, and be specified in the configuration under the ``auth`` key:

.. code-block:: php

    <?php
    return [
        // ...

        'auth' => new My\Custom\UserLookupAdapter([
            'some' => 'option',
        ]),

        // ...
    ];

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

This adapter is configured to create subdirectories inside of ``dataDir`` based on the user and the checksum of the images added to Imbo. The algorithm that generates the path simply takes the three first characters of the user and creates directories for each of them, then the full user, then a directory of each of the first characters in the image identifier, and lastly it stores the image in a file with a filename equal to the image identifier itself.

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

.. _configuration-content-negotiation:

Content negotiation for images - ``contentNegotiateImages``
-----------------------------------------------------------

By default, Imbo will do content negotiation for images. In other words, if a request is sent for an image with the ``Accept``-header ``image/jpeg``, it will try to deliver the image in JPEG-format.

If what you want is for images to be delivered in the format they were uploaded in, you can set ``contentNegotiateImages`` to ``false`` in the configuration. This will also ensure Imbo does not include ``Accept`` in the ``Vary``-header for image requests, which will make caching behind reverse proxies more efficient.

You are still able to convert between formats by specifying an extension when requesting the image (`.jpg`, `.png`, `.gif` etc).

.. _configuration-event-listeners:

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
