Requirements
============

Imbo requires a web server (for instance `Apache`_, `Nginx`_ or `Lighttpd`_) running PHP >= 5.6 and the `Imagick PECL extension`_ extension for PHP (with at least ImageMagick 6.3.8).

You will also need a backend for storing image information, like for instance `MongoDB`_ or `MySQL`_. If you want to use MongoDB as a database and/or `GridFS`_ for storage, you will need to install the `MongoDB PECL extension`_ and the `MongoDB PHP library`_, and if you want to use a :abbr:`RDBMS (Relational Database Management System)` like MySQL, you will need to install the `Doctrine Database Abstraction Layer`_.

.. _Apache: https://httpd.apache.org/
.. _Nginx: https://nginx.org/
.. _Lighttpd: https://www.lighttpd.net/
.. _Imagick PECL extension: https://pecl.php.net/package/imagick
.. _MongoDB PECL extension: https://pecl.php.net/package/mongodb
.. _MongoDB: https://www.mongodb.org/
.. _GridFS: https://docs.mongodb.org/manual/core/gridfs/
.. _MongoDB PHP library: https://packagist.org/packages/mongodb/mongodb
.. _MySQL: https://www.mysql.com
.. _Doctrine Database Abstraction Layer: http://www.doctrine-project.org/projects/dbal.html
