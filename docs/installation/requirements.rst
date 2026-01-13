Requirements
============

Imbo requires a web server (for instance `Apache`_ or `Nginx`_) running PHP >= 8.3 and the `Imagick PECL extension`_ extension for PHP.

You will also need a backend for storing image information, like for instance `MongoDB`_ or `MySQL`_. If you want to use MongoDB as a database and/or `GridFS`_ for storage, you will need to install the `MongoDB PECL extension`_ and the `MongoDB PHP library`_. :abbr:`RDBMS (Relational Database Management System)` backends use the `PDO extension`_.

.. _Apache: https://httpd.apache.org/
.. _Nginx: https://nginx.org/
.. _Imagick PECL extension: https://pecl.php.net/package/imagick
.. _MongoDB PECL extension: https://pecl.php.net/package/mongodb
.. _MongoDB: https://www.mongodb.org/
.. _GridFS: https://docs.mongodb.org/manual/core/gridfs/
.. _MongoDB PHP library: https://packagist.org/packages/mongodb/mongodb
.. _MySQL: https://www.mysql.com
.. _PDO extension: https://www.php.net/manual/en/book.pdo.php
