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

    array(
        'transformations' => array(
            'whitelist' => array(
                'border',
            ),
        ),
    )

means that the access token will **not** be enforced for the :ref:`border <border-transformation>` transformation.

.. code-block:: php

    array(
        'transformations' => array(
            'blacklist' => array(
                'border',
            ),
        ),
    )

means that the access token will be enforced **only** for the :ref:`border <border-transformation>` transformation.

If both ``whitelist`` and ``blacklist`` are specified all transformations will require an access token unless it's included in ``whitelist``.

This event listener is included in the default configuration file without specifying any transformation filters:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'accessToken' => 'Imbo\EventListener\AccessToken',
        ),

        // ...
    );

Disable this event listener with care. Installations with no access token check is open for `DoS <http://en.wikipedia.org/wiki/Denial-of-service_attack>`_ attacks.

.. _authenticate-event-listener:

Authenticate
++++++++++++

This event listener enforces the usage of signatures on all write requests against user-specific resources. You can read more about how the actual signature check works in the :ref:`signing-write-requests` section in the :doc:`../usage/api` chapter.

To enforce the signature check for all write requests this event listener subscribes to the following events:

* ``image.put``
* ``image.post``
* ``image.delete``
* ``metadata.put``
* ``metadata.post``
* ``metadata.delete``

This event listener does not support any parameters and is enabled per default like this:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'authenticate' => 'Imbo\EventListener\Authenticate',
        ),

        // ...
    );

Disable this event listener with care. User agents can delete all your images and metadata if this listener is disabled.

.. _auto-rotate-image-event-listener:

Auto rotate image
+++++++++++++++++

This event listener will auto rotate new images based on metadata embedded in the image itself (`EXIF <http://en.wikipedia.org/wiki/Exchangeable_image_file_format>`_).

The listener does not support any parameters and can be enabled like this:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'autoRotateListener' => 'Imbo\EventListener\AutoRotateImage',
        ),

        // ...
    );

If you enable this listener all new images added to Imbo will be auto rotated based on the EXIF data. This might also cause the image identifier sent in the response to be different from the one used in the URI when storing the image. This can happen with all event listeners which can possibly modify the image before storing it.

.. _cors-event-listener:

CORS (Cross-Origin Resource Sharing)
++++++++++++++++++++++++++++++++++++

This event listener can be used to allow clients such as web browsers to use Imbo when the client is located on a different origin/domain than the Imbo server is. This is implemented by sending a set of `CORS <http://en.wikipedia.org/wiki/Cross-origin_resource_sharing>`_-headers on specific requests, if the origin of the request matches a configured domain.

The event listener can be configured on a per-resource and per-method basis, and will therefore listen to any related events. If enabled without any specific configuration, the listener will allow and respond to the **GET**, **HEAD** and **OPTIONS** methods on all resources. Note however that no origins are allowed by default and that a client will still need to provide a valid access token, unless the :ref:`Access token listener <access-token-event-listener>` is disabled.

Here is an example on how to enable the CORS listener:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'cors' => array(
                'listener' => 'Imbo\EventListener\Cors',
                'params' => array(
                    array(
                        'allowedOrigins' => array('http://some.origin'),
                        'allowedMethods' => array(
                            'image'  => array('GET', 'HEAD', 'PUT'),
                            'images' => array('GET', 'HEAD'),
                        ),
                        'maxAge' => 3600,
                    ),
                ),
            ),
        ),

        // ...
    );

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

* ``image.put``
* ``db.image.insert``

and has the following parameters:

``$allowedTags``
    The tags you want to be populated as metadata. Defaults to ``exif:*``. When specified it will override the default value, so if you want to register all ``exif`` and ``date`` tags for example, you will need to specify them both.

and is enabled like this:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'exifMetadata' => array(
                'listener' => 'Imbo\EventListener\ExifMetadata',
                'params' => array(
                    array('exif:*', 'date:*', 'png:gAMA'),
                ),
            ),
        ),

        // ...
    );

which would allow all ``exif`` and ``date`` properties as well as the ``png:gAMA`` property. If you want to store **all** tags as metadata, use ``array('*')`` as filter.

Image transformation cache
++++++++++++++++++++++++++

This event listener enables caching of image transformations. Read more about image transformations in the :ref:`image-transformations` section.

To achieve this the listener subscribes to the following events:

* ``image.get``
* ``response.send``
* ``image.delete``

The event listener has one parameter:

``$path``
    Root path where the cached images will be stored.

and is enabled like this:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'imageTransformationCache' => array(
                'listener' => 'Imbo\EventListener\ImageTransformationCache',
                'params' => array('/path/to/cache'),
            ),
        ),

        // ...
    );

.. note::
    This event listener uses a similar algorithm when generating file names as the :ref:`filesystem-storage-adapter` storage adapter.

.. warning::
    It can be wise to purge old files from the cache from time to time. If you have a large amount of images and present many different variations of these the cache will use up quite a lot of storage.

    An example on how to accomplish this:

    .. code-block:: bash

        $ find /path/to/cache -ctime +7 -type f -delete

    The above command will delete all files in ``/path/to/cache`` older than 7 days and can be used with for instance `crontab <http://en.wikipedia.org/wiki/Cron>`_.

.. _imagick-event-listener:

Imagick
+++++++

This event listener is required by the image transformations that is included in Imbo, and there is no configuration options for it. Unless you plan on exchanging all the internal image transformations with your own (for instance implemented using Gmagick or GD) you are better off leaving this as-is.

.. _max-image-size-event-listener:

Max image size
++++++++++++++

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

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'maxImageSizeListener' => array(
                'listener' => 'Imbo\EventListener\MaxImageSize',
                'params' => array(1024, 768),
            ),
        ),

        // ...
    );

which would effectively downsize all images exceeding a ``width`` of ``1024`` or a ``height`` of ``768``. The aspect ratio will be kept.

Metadata cache
++++++++++++++

This event listener enables caching of metadata fetched from the backend so other requests won't need to go all the way to the backend to fetch it. To achieve this the listener subscribes to the following events:

* ``db.metadata.load``
* ``db.metadata.delete``
* ``db.metadata.update``
* ``db.image.delete``

and has the following parameters:

``Imbo\Cache\CacheInterface $cache``
    An instance of a cache adapter. Imbo ships with :ref:`apc-cache` and :ref:`memcached-cache` adapters, and both can be used for this event listener. If you want to use another form of caching you can simply implement the ``Imbo\Cache\CacheInterface`` interface and pass an instance of the custom adapter to the constructor of the event listener. See the :ref:`custom-cache-adapter` section for more information regarding this. Here is an example that uses the APC adapter for caching:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'metadataCache' => array(
                'listener' => 'Imbo\EventListener\MetadataCache',
                'params' => array(
                    new Imbo\Cache\APC('imbo'),
                ),
            ),
        ),

        // ...
    );


.. _stats-access-event-listener:

Stats access
++++++++++++

This event listener controls the access to the :ref:`stats resource <stats-resource>` by using simple white-/blacklists containing IPv4 and/or IPv6 addresses. `CIDR-notations <http://en.wikipedia.org/wiki/CIDR#CIDR_notation>`_ are also supported.

This listener is enabled per default, and only allows ``127.0.0.1`` and ``::1`` to access the statistics:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'statsAccess' => array(
                'listener' => 'Imbo\EventListener\StatsAccess',
                'params' => array(
                    array(
                        'whitelist' => array('127.0.0.1', '::1'),
                        'blacklist' => array(),
                    )
                ),
            ),
        ),

        // ...
    );

If the whitelist is populated, only the listed IP addresses/subnets will gain access. If the blacklist is populated only the listed IP addresses/subnets will be denied access. If both lists are populated the IP address of the client must be present in the whitelist to gain access. If an IP address is present in both lists, it will not gain access.

Varnish HashTwo
+++++++++++++++

This event listener can be enabled if you want Imbo to send a `HashTwo header <https://www.varnish-software.com/blog/advanced-cache-invalidation-strategies>`_ optionally used by `Varnish <https://www.varnish-software.com/>`_. The listener when enabled subscribes to the following event:

* ``image.get``

The event listener has the following parameters:

``$header`` (optional)
    Set the header name to use. Defaults to ``X-HashTwo``.

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'hashTwo' => 'Imbo\EventListener\VarnishHashTwo',
        ),

        // ...
    );

The value of the header is a combination of the public key and the current image identifier, separated by ``|``.
