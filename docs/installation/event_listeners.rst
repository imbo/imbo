Customize your Imbo installation with event listeners
=====================================================

Imbo ships with a collection of event listeners for you to use. Some of them are enabled in the default configuration file. Image transformations are also technically event listeners, but will not be covered in this chapter. Read the :doc:`../usage/image-transformations` chapter for more information regarding the image transformations.

.. contents::
    :local:
    :depth: 1

.. _access-token-event-listener:

Access token
++++++++++++

This event listener enforces the usage of access tokens on all read requests against user-specific resources. You can read more about how the actual access tokens work in the :ref:`access-tokens` part of the :doc:`../usage/api` chapter.

To enforce the access token check this event listener subscribes to the following events:

* ``user.get``
* ``user.head``
* ``images.get``
* ``images.head``
* ``image.get``
* ``image.head``
* ``metadata.get``
* ``metadata.head``

This event listener has a single parameter that can be used to whitelist and/or blacklist certain image transformations, used when the current request is against an image resource. The parameter is an array with a single key: ``transformations``. This is another array with two keys: ``whitelist`` and ``blacklist``. These two values are arrays where you specify which transformation(s) to whitelist or blacklist. The names of the transformations are the same as the ones used in the request. See :ref:`image-transformations` for a complete list of the supported transformations.

Use ``whitelist`` if you want the listener to skip the access token check for certain transformations, and ``blacklist`` if you want it to only check certain transformations:

.. code-block:: php

    [
        'transformations' => [
            'whitelist' => [
                'border',
            ],
        ],
    ]

means that the access token will **not** be enforced for the :ref:`border <border-transformation>` transformation.

.. code-block:: php

    [
        'transformations' => [
            'blacklist' => [
                'border',
            ],
        ],
    ]

means that the access token will be enforced **only** for the :ref:`border <border-transformation>` transformation.

If both ``whitelist`` and ``blacklist`` are specified all transformations will require an access token unless it's included in ``whitelist``.

This event listener is included in the default configuration file without specifying any transformation filters:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'accessToken' => 'Imbo\EventListener\AccessToken',
        ],

        // ...
    ];

Disable this event listener with care. Installations with no access token check is open for `DoS <http://en.wikipedia.org/wiki/Denial-of-service_attack>`_ attacks.

.. _authenticate-event-listener:

Authenticate
++++++++++++

This event listener enforces the usage of signatures on all write requests against user-specific resources. You can read more about how the actual signature check works in the :ref:`signing-write-requests` section in the :doc:`../usage/api` chapter.

To enforce the signature check for all write requests supported by Imbo this event listener subscribes to the following events:

* ``images.post``
* ``image.delete``
* ``metadata.put``
* ``metadata.post``
* ``metadata.delete``

This event listener does not support any parameters and is enabled per default like this:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'authenticate' => 'Imbo\EventListener\Authenticate',
        ],

        // ...
    ];

Disable this event listener with care. User agents can delete all your images and metadata if this listener is disabled.

.. _auto-rotate-image-event-listener:

Auto rotate image
+++++++++++++++++

This event listener will auto rotate new images based on metadata embedded in the image itself (`EXIF <http://en.wikipedia.org/wiki/Exchangeable_image_file_format>`_).

The listener does not support any parameters and can be enabled like this:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'autoRotateListener' => 'Imbo\EventListener\AutoRotateImage',
        ],

        // ...
    ];

If you enable this listener all new images added to Imbo will be auto rotated based on the EXIF data. This might also cause the image identifier sent in the response to be different from the one used in the URI when storing the image. This can happen with all event listeners which can possibly modify the image before storing it.

.. _cors-event-listener:

CORS (Cross-Origin Resource Sharing)
++++++++++++++++++++++++++++++++++++

This event listener can be used to allow clients such as web browsers to use Imbo when the client is located on a different origin/domain than the Imbo server is. This is implemented by sending a set of `CORS <http://en.wikipedia.org/wiki/Cross-origin_resource_sharing>`_-headers on specific requests, if the origin of the request matches a configured domain.

The event listener can be configured on a per-resource and per-method basis, and will therefore listen to any related events. If enabled without any specific configuration, the listener will allow and respond to the **GET**, **HEAD** and **OPTIONS** methods on all resources. Note however that no origins are allowed by default and that a client will still need to provide a valid access token, unless the :ref:`Access token listener <access-token-event-listener>` is disabled.

Here is an example on how to enable the CORS listener:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'cors' => [
                'listener' => 'Imbo\EventListener\Cors',
                'params' => [
                    'allowedOrigins' => ['http://some.origin'],
                    'allowedMethods' => [
                        'image'  => ['GET', 'HEAD'],
                        'images' => ['GET', 'HEAD', 'POST'],
                    ],
                    'maxAge' => 3600,
                ],
            ],
        ],

        // ...
    ];

Below all supported parameters are listed:

``allowedOrigins``
    is an array of allowed origins. Specifying ``*`` as a value in the array will allow any origin.

``allowedMethods``
    is an associative array where the keys represent the resource (``shorturl``, ``status``, ``stats``, ``user``, ``images``, ``image`` and ``metadata``) and the values are arrays of HTTP methods you wish to open up.

``maxAge``
    specifies how long the response of an OPTIONS-request can be cached for, in seconds. Defaults to 3600 (one hour).

EXIF metadata
+++++++++++++

This event listener can be used to fetch the EXIF-tags from uploaded images and adding them as metadata. Enabling this event listener will not populate metadata for images already added to Imbo.

The event listener subscribes to the following events:

* ``images.post``
* ``db.image.insert``

and the parameters given to the event listener supports a single element:

``allowedTags``
    The tags you want to be populated as metadata. Defaults to ``exif:*``. When specified it will override the default value, so if you want to register all ``exif`` and ``date`` tags for example, you will need to specify them both.

and is enabled like this:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'exifMetadata' => [
                'listener' => 'Imbo\EventListener\ExifMetadata',
                'params' => [
                    'allowedTags' => ['exif:*', 'date:*', 'png:gAMA'],
                ],
            ],
        ],

        // ...
    ];

which would allow all ``exif`` and ``date`` properties as well as the ``png:gAMA`` property. If you want to store **all** tags as metadata, use ``['*']`` as filter.

Image transformation cache
++++++++++++++++++++++++++

This event listener enables caching of image transformations. Read more about image transformations in the :ref:`image-transformations` section.

To achieve this the listener subscribes to the following events:

* ``image.get``
* ``response.send``
* ``image.delete``

The parameters for the event listener supports a single element:

``path``
    Root path where the cached images will be stored.

and is enabled like this:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'imageTransformationCache' => [
                'listener' => 'Imbo\EventListener\ImageTransformationCache',
                'params' => [
                    'path' => '/path/to/cache',
                ],
            ],
        ],

        // ...
    ];

.. note::
    This event listener uses a similar algorithm when generating file names as the :ref:`filesystem-storage-adapter` storage adapter.

.. warning::
    It can be wise to purge old files from the cache from time to time. If you have a large amount of images and present many different variations of these the cache will use up quite a lot of storage.

    An example on how to accomplish this:

    .. code-block:: bash

        $ find /path/to/cache -ctime +7 -type f -delete

    The above command will delete all files in ``/path/to/cache`` older than 7 days and can be used with for instance `crontab <http://en.wikipedia.org/wiki/Cron>`_.

.. _image-variations-listener:

Image variations
++++++++++++++++

This event listener can be used to generate multiple variations of incoming images so that a more suitable size can be used when performing scaling transformations. This will increase the amount of data stored by Imbo, but it will also improve performance. In cases where the original images are very large and the requested transformed images are significantly smaller, the difference will be quite drastic.

The event listener has two roles, one is to generate the variations when new images are added, and the other is to pick the most fitting image variation when clients request an image with a set of transformations applied that will alter the dimensions of the image, for instance :ref:`resize <resize-transformation>` or :ref:`thumbnail <thumbnail-transformation>`.

Imbo ships with MongoDB and Doctrine adapters for storing metadata about these variations. If you want to use a different database, you can implement the ``Imbo\EventListener\ImageVariations\Database\DatabaseInterface`` interface and set the name of the class in the configuration of the event listener.

In the same way, Imbo ships three different adapters for storing the actual image variation data (the downscaled images): GridFS, Doctrine and Filesystem. See examples of their configuration below.

The event listener supports for following configuration parameters:

``(boolean) lossless``
    Have Imbo use a lossless format to store the image variations. This results in better quality images, but converting between formats can be slower and will use more disk space. Defaults to ``false``.

``(boolean) autoScale``
    Have Imbo automatically figure out the widths of the image variations (based on other parameters). Defaults to ``true``.

``(float) scaleFactor``
    The factor to use when scaling. Defaults to ``0.5`` which basically generates variants half the size of the previous one.

``(int) minDiff``
    When the difference of the width in pixels between two image variations fall below this limit, no more variants will be generated. Defaults to ``100``.

``(int) minWidth``
    Do not generate image variations that ends up with a width in pixels below this level. Defaults to ``100``.

``(int) maxWidth``
    Don't start to generate image variations before the width of the variation falls below this limit. Defaults to ``1024``.

``(array) widths``
    An array of widths to use when generating variations. This can be used together with the auto generation, and will ignore the rules related to auto generation. If you often request images with the same dimensions, you can significantly speed up the process by specifying the width here. Defaults to ``[]``.

``(array) database``
    The database adapter to use. This array has two elements:

    * ``(string) adapter``: The class name of the adapter. The class must implement the ``Imbo\EventListener\ImageVariations\Database\DatabaseInterface`` interface.
    * ``(array) params``: Parameters for the adapter (optional).

``(array) storage``
    The storage adapter to use. This array has two elements:

    * ``(string) adapter``: The class name of the adapter. The class must implement the ``Imbo\EventListener\ImageVariations\Storage\StorageInterface`` interface.
    * ``(array) params``: Parameters for the adapter (optional).

**Examples:**

1)  Automatically generate image variations

    Given the following configuration:

    .. code-block:: php

        return [
            // ...

            'eventListeners' => [
                'imageVariations' => [
                    'listener' => 'Imbo\EventListener\ImageVariations',
                    'params' => [
                        'database' => [
                            'adapter' => 'Imbo\EventListener\ImageVariations\Database\MongoDB',
                        ],
                        'storage' => [
                            'adapter' => 'Imbo\EventListener\ImageVariations\Storage\GridFS',
                        ],
                    ],
                ],
            ],

            // ...
        ];

    when adding an image with dimensions 3082 x 2259, the following variations will be generated:

    * 770 x 564
    * 385 x 282
    * 192 x 140

    When later requesting this image with for instance ``?t[]=resize:width=500`` as transformation (read more about image transformations in the :doc:`../usage/image-transformations` chapter), Imbo will choose the image which is 770 x 564 pixels and downscale it to 500 pixels in width.

2)  Specify image widths:

    Given the following configuration:

    .. code-block:: php

        return [
            // ...

            'eventListeners' => [
                'imageVariations' => [
                    'listener' => 'Imbo\EventListener\ImageVariations',
                    'params' => [
                        'database' => [
                            'adapter' => 'Imbo\EventListener\ImageVariations\Database\MongoDB',
                        ],
                        'storage' => [
                            'adapter' => 'Imbo\EventListener\ImageVariations\Storage\GridFS',
                        ],
                        'autoScale' => false,
                        'widths' => [1000, 500, 200, 100, 50],
                    ],
                ],
            ],

            // ...
        ];

    when adding an image with dimensions 3082 x 2259, the following variations will be generated:

    * 1000 x 732
    * 500 x 366
    * 200 x 146
    * 100 x 73
    * 50 x 36

    As you can see the ``minDiff`` and ``minWidth`` parameters are ignored when using the ``width`` parameter.

3)  Configuring database and storage adapters:

    As stated earlier, there are several different adapters to choose from when storing the variations metadata as well as the actual variation image files.

    The default adapter for the metadata database is MongoDB, and the default storage adapter is GridFS. They have the same configuration parameters:

    .. code-block:: php

        return [
            // ...

            'eventListeners' => [
                'imageVariations' => [
                    'listener' => 'Imbo\EventListener\ImageVariations',
                    'params' => [
                        'database' => [
                            'adapter' => 'Imbo\EventListener\ImageVariations\Database\MongoDB',
                            'params' => [
                                'databaseName' => 'imbo',
                                'server'  => 'mongodb://localhost:27017',
                                'options' => ['connect' => true, 'connectTimeoutMS' => 1000],
                            ]
                        ],
                        'storage' => [
                            'adapter' => 'Imbo\EventListener\ImageVariations\Storage\GridFS',
                            'params' => [
                                'databaseName' => 'imbo_storage',
                                'server'  => 'mongodb://localhost:27017',
                                'options' => ['connect' => true, 'connectTimeoutMS' => 1000],
                            ]
                        ],
                    ],
                ],
            ],

            // ...
        ];

    The Doctrine adapter is an alternative for storing both metadata and variation data. This adapter uses the `Doctrine Database Abstraction Layer <http://www.doctrine-project.org/projects/dbal.html>`_. When using this adapter you need to create the required tables in the RDBMS first, as specified in the :ref:`database-setup` section. Note that you can either pass a PDO instance (as the ``pdo`` parameter) or specify connection details. Example usage:

    .. code-block:: php

        return [
            // ...

            'eventListeners' => [
                'imageVariations' => [
                    'listener' => 'Imbo\EventListener\ImageVariations',
                    'params' => [
                        'database' => [
                            'adapter' => 'Imbo\EventListener\ImageVariations\Database\Doctrine',
                            'params' => [
                                'dbname'    => 'imbo',
                                'user'      => 'imbo_rw',
                                'password'  => 'imbo_password',
                                'host'      => 'localhost',
                                'driver'    => 'mysql',
                                'tableName' => 'imagevariations',

                                // OR, pass a PDO instance
                                'pdo'       => null,
                            ]
                        ],
                        'storage' => [
                            'adapter' => 'Imbo\EventListener\ImageVariations\Storage\Doctrine',
                            'params' => [] // Same as above
                        ],
                    ],
                ],
            ],

            // ...
        ];

    The third option for the storage adapter is the Filesystem adapter. It's fairly straightforward and uses a similar algorithm when generating file names as the :ref:`filesystem-storage-adapter` storage adapter. Example usage:

    .. code-block:: php

        return [
            // ...

            'eventListeners' => [
                'imageVariations' => [
                    'listener' => 'Imbo\EventListener\ImageVariations',
                    'params' => [
                        'storage' => [
                            'adapter' => 'Imbo\EventListener\ImageVariations\Storage\Filesystem',
                            'params' => [
                                'dataDir' => '/path/to/image-variation-storage'
                            ]
                        ],

                        // Use any database adapter you want
                        'database' => [
                            'adapter' => 'Imbo\EventListener\ImageVariations\Database\Doctrine',
                        ],
                    ],
                ],
            ],

            // ...
        ];

    .. note:: When using the Filesystem adapter, the ``dataDir`` **must** be specified, and **must** be writable by the web server.

.. _imagick-event-listener:

Imagick
+++++++

This event listener is required by the image transformations that is included in Imbo, and there is no configuration options for it. Unless you plan on exchanging all the internal image transformations with your own (for instance implemented using Gmagick or GD) you are better off leaving this as-is.

.. _max-image-size-event-listener:

Max image size
++++++++++++++

This event listener can be used to enforce a maximum size (height and width, not byte size) of **new** images. Enabling this event listener will not change images already added to Imbo.

The event listener subscribes to the following event:

* ``images.post``

and the parameters includes the following elements:

``width``
    The max width in pixels of new images. If a new image exceeds this limit it will be downsized.

``height``
    The max height in pixels of new images. If a new image exceeds this limit it will be downsized.

and is enabled like this:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'maxImageSizeListener' => [
                'listener' => 'Imbo\EventListener\MaxImageSize',
                'params' => [
                    'width' => 1024,
                    'height' => 768,
                ],
            ],
        ],

        // ...
    ];

which would effectively downsize all images exceeding a ``width`` of ``1024`` or a ``height`` of ``768``. The aspect ratio will be kept.

Metadata cache
++++++++++++++

This event listener enables caching of metadata fetched from the backend so other requests won't need to go all the way to the metadata backend to fetch it. To achieve this the listener subscribes to the following events:

* ``db.metadata.load``
* ``db.metadata.delete``
* ``db.metadata.update``
* ``db.image.delete``

and the parameters supports a single element:

``cache``
    An instance of a cache adapter. Imbo ships with :ref:`apc-cache` and :ref:`memcached-cache` adapters, and both can be used for this event listener. If you want to use another form of caching you can simply implement the ``Imbo\Cache\CacheInterface`` interface and pass an instance of the custom adapter to the constructor of the event listener. See the :ref:`custom-cache-adapter` section for more information regarding this. Here is an example that uses the APC adapter for caching:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'metadataCache' => [
                'listener' => 'Imbo\EventListener\MetadataCache',
                'params' => [
                    'cache' => new Imbo\Cache\APC('imbo'),
                ],
            ],
        ],

        // ...
    ];


.. _stats-access-event-listener:

Stats access
++++++++++++

This event listener controls the access to the :ref:`stats resource <stats-resource>` by using white listing of IPv4 and/or IPv6 addresses. `CIDR-notations <http://en.wikipedia.org/wiki/CIDR#CIDR_notation>`_ are also supported.

This listener is enabled per default, and only allows ``127.0.0.1`` and ``::1`` to access the statistics:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'statsAccess' => [
                'listener' => 'Imbo\EventListener\StatsAccess',
                'params' => [
                    'allow' => ['127.0.0.1', '::1'],
                ],
            ],
        ],

        // ...
    ];

The event listener also supports a notation for "allowing all", simply by placing ``'*'`` somewhere in the list:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'statsAccess' => [
                'listener' => 'Imbo\EventListener\StatsAccess',
                'params' => [
                    [
                        'allow' => ['*'],
                    ]
                ],
            ],
        ],

        // ...
    ];

The above example will allow all clients access to the statistics.

.. note: If you choose to override the configuration, remember to add the default values if you also want them, as your configuration will override the default configuration completely.

Varnish HashTwo
+++++++++++++++

This event listener can be enabled if you want Imbo to include `HashTwo headers <https://www.varnish-software.com/blog/advanced-cache-invalidation-strategies>`_ in responses to image requests. These headers can be used by `Varnish <https://www.varnish-software.com/>`_ for more effective cache invalidation strategies. The listener, when enabled, subscribes to the following events:

* ``image.get``
* ``image.head``

The parameters supports a single element:

``headerName``
    Set the header name to use. Defaults to ``X-HashTwo``.

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'hashTwo' => 'Imbo\EventListener\VarnishHashTwo',
        ],

        // ...
    ];

or, if you want to use a non-default header name:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'hashTwo' => [
                'listener' => 'Imbo\EventListener\VarnishHashTwo',
                'params' => [
                    'headerName' => 'X-Custom-HashTwo-Header-Name',
                ],
            ],
        ],

        // ...
    ];

The header appears multiple times in the response, with slightly different values::

    X-HashTwo: imbo;image;<publicKey>;<imageIdentifier>
    X-HashTwo: imbo;user;<publicKey>
