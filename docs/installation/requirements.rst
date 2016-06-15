Requirements
============

Imbo requires a web server (for instance `Apache <http://httpd.apache.org/>`_, `Nginx <http://nginx.org/en/>`_ or `Lighttpd <http://www.lighttpd.net/>`_) running `PHP >= 5.6 <http://php.net>`_ and the `Imagick <http://pecl.php.net/package/imagick>`_ extension for PHP.

You will also need a backend for storing image information, like for instance `MongoDB <http://www.mongodb.org/>`_ or `MySQL <http://www.mysql.com>`_. If you want to use MongoDB as a database and/or `GridFS <http://docs.mongodb.org/manual/core/gridfs/>`_ for storage, you will need to install the `Mongo <http://pecl.php.net/package/mongo>`_ PECL extension, and if you want to use a :abbr:`RDBMS (Relational Database Management System)` like MySQL, you will need to install the `Doctrine Database Abstraction Layer <http://www.doctrine-project.org/projects/dbal.html>`_.
