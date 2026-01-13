.. _configuration:

Configuration
=============

Imbo ships with a default configuration file that will be automatically loaded. You will have to create one or more configuration files of your own that will be merged with the default configuration by Imbo. You should never edit the default configuration file provided by Imbo.

The configuration file(s) you need to create should return arrays with configuration data. All available configuration options are covered in this chapter.

.. note::
    Imbo supports using `closures <https://www.php.net/manual/en/functions.anonymous.php>`__ for certain configuration options. The current HTTP request (``Imbo\Http\Request\Request``) will be specified as an argument when calling the closure.

    By using a closure you can extend Imbo with custom logic for most configuration options, such as switching storage modules based on the public key that signed the request. Using closures will also delay the instantiation of the classes until they are first accessed, instead of when the configuration files are loaded.

.. contents::
    :local:
    :depth: 1

Database configuration - ``database``
-------------------------------------

The database adapter you decide to use is responsible for storing metadata and basic image information, like width and height, along with the generated short URLs. Imbo ships with some different database adapters that you can use. Remember that you will not be able to switch the adapter whenever you want and expect all data to be automatically transferred. Choosing a database adapter should be a long term commitment unless you have migration scripts available.

You can either specify an adapter directly, or use a closure that will return an adapter when called. Imbo ships with the following database adapters:

.. contents::
    :local:
    :depth: 1

PostgreSQL
++++++++++

This adapter stores information in a `PostgreSQL <https://www.postgresql.org/>`__ database using the `PDO extension <https://www.php.net/pdo>`__. Use the following arguments when creating the adapter:

``dsn`` (required)
    The database DSN.

``username`` (optional)
    The username to use when connecting.

``password`` (optional)
    The password to use when connecting.

``options`` (optional)
    Options passed to the underlying PDO instance.

Examples
^^^^^^^^

1) Connect to a local instance:

.. code-block:: php

    <?php
    return [
        // ...

        'database' => new Imbo\Database\PostgreSQL(
            'pgsql:host=localhost;dbname=imbo',
            'username',
            'super-secret-password',
        ),

        // ...
    ];

MySQL
+++++

This adapter stores information in a `MySQL <https://www.mysql.com/>`__ database using the `PDO extension <https://www.php.net/pdo>`__. Use the following arguments when creating the adapter:

``dsn`` (required)
    The database DSN.

``username`` (optional)
    The username to use when connecting.

``password`` (optional)
    The password to use when connecting.

``options`` (optional)
    Options passed to the underlying PDO instance.

Examples
^^^^^^^^

1) Connect to a local instance:

.. code-block:: php

    <?php
    return [
        // ...

        'database' => new Imbo\Database\MySQL(
            'mysql:host=localhost;dbname=imbo',
            'username',
            'super-secret-password',
        ),

        // ...
    ];

SQLite
++++++

This adapter stores information in a `SQLite <https://sqlite.org/>`__ database using the `PDO extension <https://www.php.net/pdo>`__. Use the following arguments when creating the adapter:

``dsn`` (required)
    The database DSN.

``options`` (optional)
    Options passed to the underlying PDO instance.

Examples
^^^^^^^^

1) Connect to a SQLite database:

.. code-block:: php

    <?php
    return [
        // ...

        'database' => new Imbo\Database\SQLite('sqlite:/opt/databases/imbo.sq3'),

        // ...
    ];

.. _mongodb-database-adapter:

MongoDB
+++++++

This adapter stores information in a `MongoDB <https://www.mongodb.com/>`__ database using the `mongodb extension <https://pecl.php.net/package/mongodb>`__ and the `MongoDB PHP library <https://www.mongodb.com/docs/php-library/current/>`__. Use the following arguments when creating the adapter:

``databaseName`` (optional, default: ``imbo``)
    The database name.

``uri`` (optional, default: ``mongodb://localhost:27017``)
    The URI to use when connecting to the server.

``uriOptions`` (optional)
    Options passed to the underlying client.

``driverOptions`` (optional)
    Options passed to the underlying client.

Examples
^^^^^^^^

1) Connect to a local MongoDB server:

.. code-block:: php

    <?php
    return [
        // ...

        'database' => new Imbo\Database\MongoDB(),

        // ...
    ];

2) Connect to a `MongoDB replica set <https://www.mongodb.com/docs/manual/replication/>`__:

.. code-block:: php

    <?php
    return [
        // ...

        'database' => new Imbo\Database\MongoDB(
            'imbo',
            'mongodb://server1,server2,server3/?replicaSet=nameOfReplicaSet',
        ),

        // ...
    ];

Storage configuration - ``storage``
-----------------------------------

Storage adapters are responsible for storing the original images you put into Imbo. As with the database adapter it is not possible to switch the adapter without having migration scripts available to move the stored images. Choose an adapter with care.

You can either specify an adapter directly, or use a closure that will return an adapter when called. Imbo ships with the following storage adapters:

.. contents::
    :local:
    :depth: 1

.. _filesystem-storage-adapter:

Filesystem
++++++++++

This adapter stores all images on the file system. Use the following arguments when creating the adapter:

``baseDir`` (required)
    The base directory for the images.

This adapter is configured to create subdirectories inside of ``baseDir`` based on the user and the identifier of the images added to Imbo. The algorithm that generates the path takes the three first characters of the user and creates directories for each of them, then the complete user, then a directory of each of the first characters in the image identifier, and lastly it stores the image in a file with a filename equal to the image identifier itself. For instance, an image stored under the user ``username`` with the image identifier ``5c01e554-9fca-4231-bb95-a6eabf259b64`` would be stored as ``<baseDir>/u/s/e/username/5/c/0/5c01e554-9fca-4231-bb95-a6eabf259b64``.

Examples
^^^^^^^^

1) Store images in ``/path/to/images``:

.. code-block:: php

    <?php
    return [
        // ...

        'storage' => new Imbo\Storage\Filesystem('/path/to/images'),

        // ...
    ];

GridFS
++++++

This adapter stores images in MongoDB using `GridFS <https://www.mongodb.com/docs/manual/core/gridfs/>`__. Use the following arguments when creating the adapter:

``databaseName`` (optional, default: ``imbo_storage``)
    The database name.

``uri`` (optional, default: ``mongodb://localhost:27017``)
    The URI to use when connecting to the server.

``uriOptions``
    Options passed to the underlying client.

``driverOptions``
    Options passed to the underlying client.

``bucketOptions``
    Options passed to the underlying client.

Examples
^^^^^^^^

1) Connect to a local MongoDB server:

.. code-block:: php

    <?php
    return [
        // ...

        'storage' => new Imbo\Storage\GridFS(),

        // ...
    ];

2) Connect to a replica set:

.. code-block:: php

    <?php
    return [
        // ...

        'storage' => new Imbo\Storage\GridFS(
            'imbo_storage',
            'mongodb://server1,server2,server3/?replicaSet=nameOfReplicaSet',
        ),

        // ...
    ];

Amazon S3
+++++++++

This adapter is available as a `separate package <https://github.com/imbo/imbo-s3-adapters>`__. Please refer to the documentation for configuration and installation.

Backblaze B2
++++++++++++

This adapter is available as a `separate package <https://github.com/imbo/imbo-b2-adapters>`__. Please refer to the documentation for configuration and installation.

.. _access-control-configuration:

Access control - ``accessControl``
----------------------------------

Imbo catalogs stored images under a ``user``. To add an image to a given user, you need a public and private key pair. This pair is used to sign requests to Imbo's API and ensures that the API can't be accessed without knowing the private key.

Multiple public keys can be given access to a user, and you can also configure a public key to have access to multiple users. It's important to note that a ``user`` does not have to be created in any way - as long as a public key is defined to have access to a given user, you're ready to start adding images.

Public keys can be configured to have varying degrees of access. For instance, you might want one public key for write operations (such as adding and deleting images) and a different public key for read operations (such as viewing images and applying transformations to them). Access is defined on a ``resource`` basis - which basically translates to an API endpoint and an HTTP method. To retrieve an image, for instance, you would give access to the ``image.get`` resource.

Specifying a long list of resources can get tedious, so Imbo also supports ``resource groups`` - basically just a list of different resources. When creating access rules for a public key, these can be used instead of specifying specific resources.

For the private keys you can for instance use a `SHA-256 <https://en.wikipedia.org/wiki/SHA-2>`__ hash of a random value. The private key is used by clients to sign requests, and if you accidentally give away your private key users can use it to delete all your images (given the public key it belongs to has write access). Make sure not to generate a private key that is easy to guess (like for instance the MD5 or SHA-256 hash of the public key). Imbo does not require the private key to be in a specific format, so you can also use regular passwords/passphrases if you want. The key itself should **never** be a part of the payload sent to/from the server.

Imbo ships with a small command line tool that can be used to generate cryptographically secure private keys. The tool is located in the ``bin`` directory of the Imbo installation:

.. code-block:: bash

    $ ./bin/imbo generate-private-key
    <random key>

Both the public and private key can be changed whenever you want as long as you remember to change them in both the server configuration and in the client you use. The user can not be changed easily as database and storage adapters use it when storing/fetching images and metadata.

.. contents::
    :local:
    :depth: 1

.. _access-control-simple-array-adapter:

Simple array adapter
++++++++++++++++++++

The most basic adapter is the ``Imbo\Auth\AccessControl\Adapter\SimpleArrayAdapter``, which has a number of trade-offs in favor of being easy to set up. Mainly, it expects the public key to have the same name as the user it should have access to, and that the public key should be given full read+write access to all resources belonging to that user.

.. code-block:: php

    <?php
    return [
        // ...

        'accessControl' => new Imbo\Auth\AccessControl\Adapter\SimpleArrayAdapter([
            'some-user' => 'my-super-secret-private-key',
            'other-user' => 'other-super-secret-private-key',
        ]),

        // ...
    ];

Array adapter
+++++++++++++

It's usually a good idea to have separate public keys for read-only and read+write operations. You can achieve this by using the more flexible ``Imbo\Auth\AccessControl\Adapter\ArrayAdapter`` adapter:

.. code-block:: php

    <?php
    return [
        // ...

        'accessControl' => new Imbo\Auth\AccessControl\Adapter\ArrayAdapter([
            [
                'publicKey' => 'some-read-only-pubkey',
                'privateKey' => 'some-private-key',
                'acl' => [
                    [
                        'resources' => Imbo\Resource::getReadOnlyResources(),
                        'users' => ['some-user'],
                    ],
                ],
            ],
            [
                'publicKey' => 'some-read-write-pubkey',
                'privateKey' => 'some-other-private-key',
                'acl' => [
                    [
                        'resources' => Imbo\Resource::getReadWriteResources(),
                        'users' => ['some-user'],
                    ],
                ],
            ],
        ]),

        // ...
    ];

As you can see from the example, this adapter is much more flexible than the :ref:`access-control-simple-array-adapter`. The example only shows part of this flexibility. You can also provide resource groups and multiple access control rules per public key. The following example shows this more clearly:

.. code-block:: php

    <?php
    return [
        // ...

        'accessControl' => new Imbo\Auth\AccessControl\Adapter\ArrayAdapter([
            [
                // A unique public key matching the following regular expression: [A-Za-z0-9_-]{1,}
                'publicKey' => 'some-pubkey',

                // Some form of private key
                'privateKey' => 'some-private-key',

                // Array of rules for this public key
                'acl' => [
                    [
                        // An array of different resource names that the public key should have
                        // access to - see AdapterInterface::RESOURCE_* for available options.
                        'resources' => Imbo\Resource::getReadOnlyResources(),

                        // Names of the users which the public key should have access to.
                        'users' => ['some', 'users'],
                    ],

                    // Multiple rules can be applied in order to make a single public key have
                    // different access rights on different users
                    [
                        'resources' => Imbo\Resource::getReadWriteResources(),
                        'users' => ['different-user'],
                    ],

                    // You can also specify resource groups instead of explicitly setting them like
                    // in the above examples. Note that you cannot specify both resources and group
                    // in the same rule.
                    [
                        'group' => 'read-stats',
                        'users' => ['user1', 'user2']
                    ],
                ],
            ],
        ], [
            // Second argument to the ArrayAdapter being the available resource groups
            // Format: 'name' => ['resource1', 'resource2']
            'read-stats' => ['user.get', 'user.head', 'user.options'],
        ]),

        // ...
    ];

MongoDB adapter
+++++++++++++++

Imbo also ships with a MongoDB access control adapter, which is mutable using Imbo's API. The adapter uses the `mongodb extension <https://pecl.php.net/package/mongodb>`__ and the `MongoDB PHP Library <https://www.mongodb.com/docs/php-library/current/>`__. Use the following arguments when creating the adapter:

``databaseName`` (optional, default: ``imbo``)
    The database name.

``uri`` (optional, default: ``mongodb://localhost:27017``)
    The URI to use when connecting to the server.

``uriOptions``
    Options passed to the underlying client.

``driverOptions``
    Options passed to the underlying client.

Examples
^^^^^^^^

1) Connect to a local MongoDB server:

.. code-block:: php

    <?php
    return [
        // ...

        'accessControl' => new Imbo\Auth\AccessControl\Adapter\MongoDB(),

        // ...
    ];

When using a mutable access control adapter, you will need to create an initial public key that can subsequently be used to create other public keys. The easiest way to create public keys when using a mutable adapter is to utilize the :ref:`add-public-key command <cli-add-public-key>` provided by the CLI tool that Imbo is shipped with.

Image identifier generation - ``imageIdentifierGenerator``
----------------------------------------------------------

By default, Imbo generates a random 12-character image identifier for added images, using characters in the range ``[A-Za-z0-9_-]``.

If you wish you can change the generation process to a different method by specifying a different generator in the configuration.

.. contents::
    :local:
    :depth: 1

Random string
+++++++++++++

The default, as stated above. Use the following arguments when creating the adapter:

``length`` (optional, default: ``12``)
    The length of the randomly generated string.

Examples
^^^^^^^^

.. code-block:: php

    <?php
    return [
        // ...

        'imageIdentifierGenerator' => new Imbo\Image\Identifier\Generator\RandomString(),

        // ...
    ];

UUID
++++

Generates 36-character v4 `UUID <https://en.wikipedia.org/wiki/Universally_unique_identifier>`__\s, for instance ``f47ac10b-58cc-4372-a567-0e02b2c3d479``. This generator does not have any parameters.

Examples
^^^^^^^^

.. code-block:: php

    <?php
    return [
        // ...

        'imageIdentifierGenerator' => new Imbo\Image\Identifier\Generator\Uuid(),

        // ...
    ];

HTTP cache headers - ``httpCacheHeaders``
-----------------------------------------

Imbo ships with reasonable defaults regarding which HTTP cache header settings it sends to clients. For some resources, however, it can be difficult to figure out a good middle ground between clients asking too often and too rarely. For instance, the ``images`` resource will change every time a new image has been added - but whether that happens once a second or once a year is hard to know.

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

You are still able to convert between formats by specifying an extension when requesting the image (``.jpg``, ``.png``, ``.gif`` etc).

Rethrow any non-handled general exceptions - ``rethrowFinalException``
----------------------------------------------------------------------

If an exception occurs internally while Imbo is processing a request, the exception will be caught by the main application entry point and an appropriate error will be generated. This does however hide implementation details that can be useful if you're doing actual development on Imbo. This value is ``false`` by default.

Setting this value to ``true`` will make Imbo rethrow the exception instead of swallowing the original exception and triggering an error. In the latter case the actual stack trace will be lost, and seeing which part of the code that actually failed will be harder in a log file.

.. code-block:: php

    <?php
    return [
        // ...

        'rethrowFinalException' => true,

        // ...
    ];

Trusted proxies - ``trustedProxies``
------------------------------------

If you find yourself behind some sort of reverse proxy (like a load balancer), certain HTTP headers may be sent to you with the ``X-Forwarded-*`` prefix. For example, the ``Host`` HTTP-header is usually used to return the requested host. But when you're behind a proxy, the true host may be stored in an ``X-Forwarded-Host`` header.

Since HTTP headers can be spoofed, Imbo does not trust these proxy headers by default. If you are behind a proxy, you should manually whitelist your proxy. This can be done by specifying the IP addresses of your proxies. Example:

.. code-block:: php

    <?php
    return [
        // ...

        'trustedProxies' => [
            '192.0.0.1',
            '10.0.0.0/8', // CIDR notation also supported
        ],

        // ...
    ];

.. note:: Not all proxies set the required ``X-Forwarded-*`` headers by default.

Authentication protocol - ``authentication``
--------------------------------------------

Imbo generates access tokens and authentication signatures based on the incoming URL, and includes the protocol (by default). This can sometimes be problematic, for instance when Imbo is behind a load balancer which does not send ``X-Forwarded-Proto`` header, or if you want to use protocol-less image URLs on the client side (``//imbo.host/users/some-user/images/img``).

Setting the ``protocol`` option under ``authentication`` allows you to control how Imbo's authentication should behave. The option has the following possible values:

``incoming`` (default)
    Try to detect the incoming protocol - this is based on ``$_SERVER['HTTPS']`` or the ``X-Forwarded-Proto`` header (given the ``trustedProxies`` option is configured).

``both``
    Try to match based on both HTTP and HTTPS protocols and allow the request if any of them yields the correct signature/access token.

``http``
    Use ``http`` as the protocol, replacing ``https`` with ``http`` in the incoming URL, if that is the case.

``https``
    Use ``https`` as the protocol, replacing ``http`` with ``https`` in the incoming URL, if that is the case.

Example:

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

Imbo support event listeners that you can use to hook into Imbo at different phases without having to edit Imbo itself. An event listener is a piece of code that will be executed when a certain event is triggered from Imbo. Event listeners are added to the ``eventListeners`` part of the configuration array as associative arrays. If you want to disable some of the default event listeners specify the same key in your configuration file and set the value to ``null`` or ``false``. Keep in mind that not all event listeners should be disabled.

Event listeners can be configured in the following ways:

1) A string representing a class name of a class implementing the ``Imbo\EventListener\ListenerInteface`` interface:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'accessToken' => Imbo\EventListener\AccessToken::class,
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
            'accessToken' => fn() => new Imbo\EventListener\AccessToken(),
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
b) an instance of a class implementing the ``Imbo\EventListener\ListenerInterface`` interface
c) a closure returning an instance of a class implementing the ``Imbo\EventListener\ListenerInterface`` interface

The ``users`` element is an array that you can use if you want your listener to only be triggered for some users. The value of this is an array with two elements, ``whitelist`` and ``blacklist``, where ``whitelist`` is an array of users you **want** your listener to trigger for, and ``blacklist`` is an array of users you **don't want** your listener to trigger for. ``users`` is optional, and per default the listener will trigger for all users.

There also exists a ``params`` key that can be used to specify parameters for the event listener, if you choose to specify the listener as a string in the ``listener`` key:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'maxImageSize' => [
                'listener' => Imbo\EventListener\MaxImageSize::class,
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

Listeners added by default
++++++++++++++++++++++++++

The default configuration file includes some event listeners by default:

* :ref:`access-control-event-listener`
* :ref:`access-token-event-listener`
* :ref:`authenticate-event-listener`
* :ref:`stats-access-event-listener`
* :ref:`imagick-event-listener`
* :ref:`output-converter-event-listener`

as well as event listeners for image transformations:


* :ref:`autoRotate <auto-rotate-transformation>`
* :ref:`blur <blur-transformation>`
* :ref:`border <border-transformation>`
* :ref:`canvas <canvas-transformation>`
* :ref:`clip <clip-transformation>`
* :ref:`compress <compress-transformation>`
* :ref:`contrast <contrast-transformation>`
* :ref:`convert <convert-transformation>`
* :ref:`crop <crop-transformation>`
* :ref:`desaturate <desaturate-transformation>`
* :ref:`drawPois <drawpois-transformation>`
* :ref:`flipHorizontally <flip-horizontally-transformation>`
* :ref:`flipVertically <flip-vertically-transformation>`
* :ref:`histogram <histogram-transformation>`
* :ref:`level <level-transformation>`
* :ref:`maxSize <max-size-transformation>`
* :ref:`modulate <modulate-transformation>`
* :ref:`progressive <progressive-transformation>`
* :ref:`resize <resize-transformation>`
* :ref:`rotate <rotate-transformation>`
* :ref:`sepia <sepia-transformation>`
* :ref:`sharpen <sharpen-transformation>`
* :ref:`smartSize <smartsize-transformation>`
* :ref:`strip <strip-transformation>`
* :ref:`thumbnail <thumbnail-transformation>`
* :ref:`transpose <transpose-transformation>`
* :ref:`transverse <transverse-transformation>`
* :ref:`vignette <vignette-transformation>`
* :ref:`watermark <watermark-transformation>`

Read more about these listeners in the :doc:`../installation/event_listeners` and :doc:`../usage/image-transformations` chapters.

.. warning:: Do not remove the event listeners specified in the default configuration file unless you are absolutely sure about the consequences. Your images can potentially be deleted by anyone.

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
            'customListener' => Listener::class,
            'otherCustomListener' => OtherListener::class,
        ],

        'eventListenerInitializers' => [
            'initializerForCustomListener' => Initializer::class,
        ],
    ];

In the above example the ``Initializer`` class will be instantiated by Imbo, and in the ``__construct`` method it will create an instance of some dependency. When the event manager creates the instances of the two event listeners these will in turn be sent to the ``initialize`` method, and the same dependency will be injected into both listeners. An alternative way to accomplish this by using closures in the configuration could look something like this:

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

.. _configuration-indexredirect:

Redirect the index route - ``indexRedirect``
--------------------------------------------

The index resource (:ref:`index-resource`) lists some URLs related to the Imbo project. If you would rather the index resource redirect the client to some specific URL, set the ``indexRedirect`` configuration option to that URL:

.. code-block:: php

    <?php
    return [
        // ...

        'indexRedirect' => 'https://github.com/imbo',

        // ...
    ];

Input loaders - ``inputLoaders``
--------------------------------

The ``inputLoaders`` configuration element is an associative array of input loaders. The values in the array must be strings representing a FQCN of an input loader, or an instance of an input loader. In both cases the class specified must implement the ``Imbo\Image\InputLoader\InputLoaderInterface`` interface.

The default configuration includes a "basic" input loader, that supports the following image types:

- ``image/png``
- ``image/jpeg``
- ``image/gif``
- ``image/tiff``

To add more input loaders, specify them in the configuration file:

.. code-block:: php

    <?php
    return [
        // ...

        'inputLoaders' => [
            'custom-loader' => My\Custom\Loader::class,
            'another-custom-loader' => new My\Other\Custom\Loader(),
        ],

        // ...
    ];

Output converters - ``outputConverters``
----------------------------------------

The ``outputConverters`` configuration element is an associative array of output converters. The values in the array must be strings representing a FQCN of an output converter, or an instance of an output converter. In both cases the class specified must implement the ``Imbo\Image\OutputConverter\OutputConverterInterface`` interface.

The default configuration includes a "basic" output converter, that supports the following image types:

- ``image/png``
- ``image/jpeg``
- ``image/gif``

To add more output converters, specify them in the configuration file:

.. code-block:: php

    <?php
    return [
        // ...

        'outputConverters' => [
            'custom-converter' => My\Custom\Converter::class,
            'another-custom-converter' => new My\Other\Custom\Converter(),
        ],

        // ...
    ];

An output converter work similar to what an input loader does, and configures the current Imagick instance to return the requested image format. If the Imagick instance is updated, the plugin must call `$image->setHasBeenTransformed(true);` to tell Imbo that the content inside the Imagick instance has changed.

If your plugin returns binary data directly, call ``$image->setBlob($data)`` instead and don't call ``$image->setHasBeenTransformed(true)`` as you've handled the conversion to binary data yourself.

Optimizations - ``optimizations``
---------------------------------

Various optimizations that might be enabled or disabled. Optimizations might have some trade offs, be it speed or image quality, which is why it's possible to disable them through configuration.

``jpegSizeHint`` (default: ``true``)
    When enabled Imbo tries to calculate what the transformed output size of images will be before loading the image into Imagick, which set a hint to libjpeg that enables "shrink-on-load", which significantly increases speed or resizing.

    Trade-offs: Transformations have to adjust parameters based on new input size, some parameters will be one pixel off. Image quality should be the same for most images, but there is always the possibility of slightly worse quality.
