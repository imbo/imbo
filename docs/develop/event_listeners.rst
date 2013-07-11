Working with events and event listeners
=======================================

Imbo uses an event dispatcher to trigger certain events from inside the application that you can subscribe to by using event listeners. In this chapter you can find information regarding the events that are triggered, and how to be able to write your own event listeners for Imbo. There is also a section on the event listeners shipped with Imbo that you can configure to fit your needs.

Events
------

When implementing an event listener you need to know about the events that Imbo triggers. The most important events are combinations of the accessed resource along with the HTTP method used. Imbo currently provides these resources:

* :ref:`shorturl <shorturl-resource>`
* :ref:`stats <stats-resource>`
* :ref:`status <status-resource>`
* :ref:`user <user-resource>`
* :ref:`images <images-resource>`
* :ref:`image <image-resource>`
* :ref:`metadata <metadata-resource>`

Examples of events that are triggered:

* ``image.get``
* ``image.put``
* ``image.delete``
* ``metadata.get``
* ``status.head``
* ``stats.get``

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

.. _custom-event-listeners:

Writing an event listener
-------------------------

When writing an event listener for Imbo you can choose one of the following approaches:

1) Implement the ``Imbo\EventListener\ListenerInterface`` interface that comes with Imbo
2) Implement a callable piece of code, for instance a class with an ``__invoke`` method
3) Use an anonymous function

Below you will find examples on the approaches mentioned above.

.. note::
    Information regarding how to **attach** the event listeners to Imbo is available in the :ref:`event listener configuration <configuration-event-listeners>` section.

Implement the ``Imbo\EventListener\ListenerInterface`` interface
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Below is the complete interface with comments:

.. literalinclude:: ../../library/Imbo/EventListener/ListenerInterface.php
    :language: php
    :linenos:

The only method you need to implement is called ``getDefinition`` and that method should return an array of ``Imbo\EventListener\ListenerDefinition`` instances. Each listener definition contains an event name, a callback, a priority and an optional list of public keys, that you can set if you want your listener to only trigger for some users.

Below is an example of how the :ref:`authenticate-event-listener` event listener implements the ``getDefinition`` method:

.. code-block:: php

    <?php

    // ...

    public function getDefinition() {
        $callback = array($this, 'invoke');
        $priority = 100;
        $events = array(
            'image.put', 'image.post', 'image.delete',
            'metadata.put', 'metadata.post', 'metadata.delete'
        );

        $definition = array();

        foreach ($events as $eventName) {
            $definition[] = new ListenerDefinition($eventName, $callback, $priority);
        }

        return $definition;
    }

    public function invoke(Imbo\EventManager\EventInterface $event) {
        // Code that handles all events this listener subscribes to
    }

    // ...

The ``getDefinition`` method above has an array of event names to subscribe to and creates a listener definition for each of them, attaching the same callback for all of them along with a fixed priority. The higher the priority, the earlier in the chain the event listener will kick in. Last it simply returns the array of listener definitions.

The ``invoke`` method, when executed, receives an instance of :ref:`the event object <the-event-object>` that it can work with. The fact that the above code only uses a single callback for all events is an implementation detail. You can use different callbacks for all events if you want to.

Use a class with an ``__invoke`` method
+++++++++++++++++++++++++++++++++++++++

You can also keep the listener definition code out of the event listener entirely, and specify that piece of information in the Imbo configuration instead. An invokable class could for instance look like this:

.. code-block:: php

    <?php
    class SomeEventListener {
        public function __invoke(Imbo\EventManager\EventInterface $event) {
            // some custom code
        }
    }

where the ``$event`` object is the same as the one passed to the ``invoke`` method in the previous example.

Use an anonymous function
+++++++++++++++++++++++++

For testing and/or debugging purposes you can also write the event listener directly in the configuration, by using an anonymous function:

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
            ),
        ),

        // ...
    );

The ``$event`` object passed to the function is the same as in the previous two examples. This approach should mostly be used for testing purposes and quick hacks. More information regarding this approach is available in the :ref:`event listener configuration <configuration-event-listeners>` section.


.. _the-event-object:

The event object
----------------

The object passed to the event listeners is an instance of the ``Imbo\EventManager\EventInterface`` interface. This interface has some methods that event listeners can use:

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

``getConfig()``
    Get the complete Imbo configuration. This should be used with caution as it includes all authentication information regarding the Imbo users.

``stopPropagation($flag)``
    If you want your event listener to force Imbo to skip all following listeners for the same event, call this method with ``true``.

``propagationIsStopped()``
    This method is used by Imbo to check if a listener wants the propagation to stop. Your listener will most likely never need to use this method.

With these methods you have access to most parts of Imbo that is worth working with. Be careful when using the database and storage adapters as these grant you access to all data stored in Imbo, with both read and write permissions.

Event listeners shipped with Imbo
---------------------------------

Imbo ships with a collection of event listeners for you to use. Some of them are enabled in the default configuration file.

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

    <?php
    array('transformations' => array(
        'whitelist' => array(
            'border',
        )
    ))

means that the access token will **not** be enforced for the :ref:`border <border-transformation>` transformation.

.. code-block:: php

    <?php
    array('transformations' => array(
        'blacklist' => array(
            'border',
        )
    ))

means that the access token will be enforced **only** for the :ref:`border <border-transformation>` transformation.

If both ``whitelist`` and ``blacklist`` are specified all transformations will require an access token unless it's included in ``whitelist``.

This event listener is included in the default configuration file without specifying any transformation filters:

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

Disable this event listener with care. Installations with no access token check is open for `DDoS <http://en.wikipedia.org/wiki/DDoS>`_ attacks.

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
            'authenticate' => function() {
                return new Imbo\EventListener\Authenticate();
            },
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
            'autoRotate' => function() {
                return new Imbo\EventListener\AutoRotateImage();
            },
        ),

        // ...
    );

If you enable this listener all new images added to Imbo will be auto rotated based on the EXIF data. This might also cause the image identifier sent in the response to be different from the one used in the URI when storing the image. This can happen with all event listeners which can possibly modify the image before storing it.

CORS (Cross-Origin Resource Sharing)
++++++++++++++++++++++++++++++++++++

This event listener can be used to allow clients such as web browsers to use Imbo when the client is located on a different origin/domain than the Imbo server is. This is implemented by sending a set of CORS-headers on specific requests, if the origin of the request matches a configured domain.

The event listener can be configured on a per-resource and per-method basis, and will therefore listen to any related events. If enabled without any specific configuration, the listener will allow and respond to the **GET**, **HEAD** and **OPTIONS** methods on all resources. Note however that no origins are allowed by default and that a client will still need to provide a valid access token, unless the :ref:`Access token listener <access-token-event-listener>` is disabled.

Here is an example on how to enable the CORS listener:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'cors' => function() {
                return new Imbo\EventListener\Cors(array(
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

``allowedOrigins``
    is an array of allowed origins. Specifying ``*`` as a value in the array will allow any origin.

``allowedMethods``
    is an associative array where the keys represent the resource (``shorturl``, ``status``, ``stats``, ``user``, ``images``, ``image`` and ``metadata``) and the values are arrays of HTTP methods you wish to open up.

``maxAge``
    specifies how long the response of an OPTIONS-request can be cached for, in seconds. Defaults to 3600 (one hour).

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
    return array(
        // ...

        'eventListeners' => array(
            'exifMetadata' => function() {
                return new Imbo\EventListener\ExifMetadata(array(
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
    return array(
        // ...

        'eventListeners' => array(
            'imageTransformationCache' => function() {
                return new Imbo\EventListener\ImageTransformationCache('/path/to/cache');
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

    The above command will delete all files in ``/path/to/cache`` older than 7 days and can be used with for instance `crontab <http://en.wikipedia.org/wiki/Cron>`_.

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
            'maxImageSize' => function() {
                return new Imbo\EventListener\MaxImageSize(1024, 768);
            },
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

and has the following parameters:

``Imbo\Cache\CacheInterface $cache``
    An instance of a cache adapter. Imbo ships with :ref:`apc-cache` and :ref:`memcached-cache` adapters, and both can be used for this event listener. If you want to use another form of caching you can simply implement the ``Imbo\Cache\CacheInterface`` interface and pass an instance of the custom adapter to the constructor of the event listener. See :ref:`custom-cache-adapter` for more information regarding this. Here is an example that uses the APC adapter for caching:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'metadataCache' => function() {
                return new Imbo\EventListener\MetadataCache(new Imbo\Cache\APC('imbo'));
            },
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
            'statsAccess' => function() {
                return new Imbo\EventListener\StatsAccess(array(
                    'whitelist' => array('127.0.0.1', '::1'),
                    'blacklist' => array(),
                ));
            },
        ),

        // ...
    );

If the whitelist is populated, only the listed IP addresses/subnets will gain access. If the blacklist is populated only the listed IP addresses/subnets will be denied access. If both lists are populated the IP address of the client must be present in the whitelist to gain access. If an IP address is present in both lists, it will not gain access.

Cache adapters
--------------

If you want to leverage caching in a custom event listener, Imbo ships with some different solutions:

.. _apc-cache:

APC
+++

This adapter uses the `APCu <http://pecl.php.net/apcu>`_ extension for caching. If your Imbo installation consists of a single httpd this is a good choice. The adapter has the following parameters:

``$namespace`` (optional)
    A namespace for your cached items. For instance: "imbo"

Example:

.. code-block:: php

    <?php
    $adapter = new Imbo\Cache\APC('imbo');
    $adapter->set('key', 'value');

    echo $adapter->get('key'); // outputs "value"
    echo apc_fetch('imbo:key'); // outputs "value"

.. _memcached-cache:

Memcached
+++++++++

This adapter uses `Memcached <http://pecl.php.net/memcached>`_ for caching. If you have multiple httpd instances running Imbo this adapter lets you share the cache between all instances automatically by letting the adapter connect to the same Memcached daemon. The adapter has the following parameters:

``$memcached``
    An instance of the pecl/memcached class.

``$namespace`` (optional)
    A namespace for your cached items. For instance: "imbo".

Example:

.. code-block:: php

    <?php
    $memcached = new Memcached();
    $memcached->addServer('hostname', 11211);

    $adapter = new Imbo\Cache\Memcached($memcached, 'imbo');
    $adapter->set('key', 'value');

    echo $adapter->get('key'); // outputs "value"
    echo $memcached->get('imbo:key'); // outputs "value"

.. _custom-cache-adapter:

Implement a custom cache adapter
++++++++++++++++++++++++++++++++

If you want to use some other cache mechanism an interface exists (``Imbo\Cache\CacheInterface``) for you to implement:

.. literalinclude:: ../../library/Imbo/Cache/CacheInterface.php
    :language: php
    :linenos:

If you choose to implement this interface you can also use your custom cache adapter for all the event listeners Imbo ships with that leverages a cache.

If you implement an adapter that you think should be a part of Imbo feel free to send a pull request on `GitHub <https://github.com/imbo/imbo>`_.
