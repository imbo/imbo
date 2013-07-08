Working with events and event listeners
=======================================

Imbo uses an event dispatcher to trigger certain events from inside the application that you can subscribe to by using event listeners. In this chapter you can find information regarding the events that is triggered, and how to be able to write your own listeners. There is also a section on the event listeners shipped with Imbo that you can configure to fit your needs.

Event listeners
---------------

Imbo ships with a collection of event listeners for you to use. Some of them are enabled in the default configuration file.

.. contents::
    :local:
    :depth: 1

.. _access-token-event-listener:

Access token
++++++++++++

This event listener enforces the usage of access tokens on all read requests against user-specific resources. You can read more about how the actual access tokens works in the :ref:`access-tokens` topic in the :doc:`../usage/api` section.

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

.. _authenticate-event-listener:

Authenticate
++++++++++++

This event listener enforces the usage of signatures on all write requests against user-specific resources. You can read more about how the actual signature check works in the :ref:`signing-write-requests` topic in the :doc:`../usage/api` section.

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

.. _auto-rotate-image-event-listener:

Auto rotate image
+++++++++++++++++

This event listener will auto rotate new images based on metadata embedded in the image itself (`EXIF`_).

.. _EXIF: http://en.wikipedia.org/wiki/Exchangeable_image_file_format

The listener does not support any parameters and can be enabled like this:

.. code-block:: php

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
++++++++++++++++++++++++++++++++++++

This event listener can be used to allow clients such as web browsers to use Imbo when the client is located on a different origin/domain than the Imbo server is. This is implemented by sending a set of CORS-headers on specific requests, if the origin of the request matches a configured domain.

The event listener can be configured on a per-resource and per-method basis, and will therefore listen to any related events. If enabled without any specific configuration, the listener will allow and respond to the **GET**, **HEAD** and **OPTIONS** methods on all resources. Note however that no origins are allowed by default and that a client will still need to provide a valid access token, unless the :ref:`access-token-event-listener` listener is disabled.

To enable the listener, use the following:

.. code-block:: php

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
+++++++++++++

This event listener can be used to fetch the EXIF-tags from uploaded images and adding them as metadata. Enabling this event listener will not populate metadata for images already added to Imbo.

The event listener subscribes to the following events:

* ``image.put``
* ``db.image.insert``

and has the following parameters:

``$allowedTags``
    The tags you want to be populated as metadata, if present. Optional - by default all tags are added.

and is enabled like this:

.. code-block:: php

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
++++++++++++++++++++++++++

This event listener enables caching of image transformations. Read more about image transformations in the :ref:`image-transformations` section.

To achieve this the listener subscribes to the following events:

* ``image.get`` (both before and after the main application logic)
* ``image.delete``

The event listener has one parameter:

``$path``
    Root path where the cached images will be stored.

and is enabled like this:

.. code-block:: php

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
    This event listener uses a similar algorithm when generating file names as the :ref:`filesystem-storage-adapter` storage adapter.

.. warning::
    It can be wise to purge old files from the cache from time to time. If you have a large amount of images and present many different variations of these the cache will use up quite a lot of storage.

    An example on how to accomplish this:

    .. code-block:: bash

        $ find /path/to/cache -ctime +7 -type f -delete

    The above command will delete all files in /path/to/cache older than 7 days and can be used with for instance `crontab`_.

.. _crontab: http://en.wikipedia.org/wiki/Cron

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
++++++++++++++

This event listener enables caching of metadata fetched from the backend so other requests won't need to go all the way to the backend to fetch metadata. To achieve this the listener subscribes to the following events:

* ``db.metadata.load``
* ``db.metadata.delete``
* ``db.metadata.update``

and has the following parameters:

``Imbo\Cache\CacheInterface $cache``
    An instance of a cache adapter. Imbo ships with :ref:`apc-cache` and :ref:`memcached-cache` adapters, and both can be used for this event listener. If you want to use another form of caching you can simply implement the ``Imbo\Cache\CacheInterface`` interface and pass an instance of the custom adapter to the constructor of the event listener. Here is an example that uses the APC adapter for caching:

.. code-block:: php

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


.. _stats-access-event-listener:

Stats access
++++++++++++

This event listener controls the access to the :ref:`stats endpoint <stats-resource>` by using simple white-/blacklists containing IP addresses.

This listener is enabled per default, and only allows ``127.0.0.1`` to access the statistics:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'statsAccess' => function() {
                return new Imbo\EventListener\StatsAccess(array(
                    'whitelist' => array('127.0.0.1'),
                    'blacklist' => array(),
                ));
            },
        ),

        // ...
    );

If the whitelist is populated, only the listed IP addresses will gain access. If the blacklist is populated only the listed IP addresses will be denied access. If both lists are populated the IP address of the client must be present in the whitelist to gain access. If an IP address is present in both lists, it will not gain access.

Events
------

When implementing an event listener you need to know about the events that Imbo triggers. The most important events are combinations of the accessed resource along with the HTTP method used. Imbo currently provides five resources:

* :ref:`status <status-resource>`
* :ref:`user <user-resource>`
* :ref:`images <images-resource>`
* :ref:`image <image-resource>`
* :ref:`metadata <metadata-resource>`

Examples of events that is triggered:

* ``image.get``
* ``image.put``
* ``image.delete``
* ``metadata.get``
* ``status.head``

As you can see from the above examples the events are built up by the resource name and the HTTP method, lowercased and separated by ``.``.

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

.. _the-event-object:

The event object
----------------

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

