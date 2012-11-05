Cache adapters
==============

If you want to leverage caching in a custom event listener Imbo ships with some different solutions:

.. _apc-cache:

APC
---

This adapter uses the `APC`_ extension for caching. If your Imbo installation consists of a single httpd this is a good choice. The adapter has the following parameters:

``$namespace`` (optional)
    A namespace for your cached items. For instance: "imbo"

Example
~~~~~~~

.. code-block:: php
    :linenos:

    <?php
    $adapter = new Imbo\Cache\Apc('imbo');
    $adapter->set('key', 'value');

    echo $adapter->get('key'); // outputs "value"

.. _memcached-cache:

Memcached
---------

This adapter uses `Memcached`_ for caching. If you have multiple httpd instances running Imbo this adapter lets you share the cache between all instances automatically by letting the adapter connect to the same Memcached daemon. The adapter has the following parameters:

``$memcached``
    An instance of the pecl/memcached class.

``$namespace`` (optional)
    A namespace for your cached items. For instance: "imbo".

Example
~~~~~~~

.. code-block:: php
    :linenos:

    <?php
    $memcached = new Memcached();
    $memcached->addServer('hostname', 11211);

    $adapter = new Imbo\Cache\Memcached($memcached, 'imbo');
    $adapter->set('key', 'value');

    echo $adapter->get('key'); // outputs "value"

Custom adapter
--------------

If you want to use some other cache mechanism an interface exists (``Imbo\Cache\CacheInterface``) for you to implement:

.. literalinclude:: ../../library/Imbo/Cache/CacheInterface.php
    :language: php
    :linenos:

If you choose to implement this interface you can also use your custom cache adapter for all the event listeners Imbo ships with that leverages a cache.

If you implement an adapter that you think should be a part of Imbo feel free to send a pull request to the `project over at GitHub`_.

.. _project over at GitHub: https://github.com/imbo/imbo
.. _APC: http://pecl.php.net/apc
.. _Memcached: http://pecl.php.net/memcached
