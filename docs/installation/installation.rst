.. _installation:

Installation
============

To install Imbo on the server you can choose between two different methods, :ref:`Composer <using-composer>` (recommended) or :ref:`git clone <git-clone>`.

.. _using-composer:

Using composer
--------------

The recommended way of installing Imbo is by creating a ``composer.json`` file for your installation, and then install Imbo and optional 3rd party plug-ins and/or image transformations via `Composer <https://getcomposer.org>`_. You will need the following directory structure for this to work::

    /path/to/install/composer.json
    /path/to/install/config.php

where the ``composer.json`` file can contain:

.. code-block:: json

    {
      "name": "yourname/imbo",
      "require": {
        "imbo/imbo": "dev-master"
      }
    }

and the ``config.php`` file is your :ref:`Imbo configuration <configuration>`. If you want to install 3rd party transformations and/or for instance the Doctrine DBAL library simply add these to the ``require`` object in your ``composer.json``:

.. code-block:: json

    {
      "name": "yourname/imbo",
      "require": {
        "imbo/imbo": "dev-master",
        "rexxars/imbo-hipsta": "dev-master",
        "doctrine/dbal": "2.*"
      }
    }

Regarding the Imbo version you are about to install you can use ``dev-master`` for the latest released version, or you can use a specific version if you want to. Head over to `Packagist <https://packagist.org/packages/imbo/imbo>`_ to see the available versions.

When you have created the ``composer.json`` file you will need to install Imbo by using Composer:

.. code-block:: bash

    mkdir /path/to/install; cd /path/to/install
    curl -s https://getcomposer.org/installer | php
    php composer.phar install -o --no-dev

After composer has finished installing Imbo and optional dependencies the Imbo installation will reside in ``/path/to/install/vendor/imbo/imbo``. The correct web server document root in this case would be ``/path/to/install/vendor/imbo/imbo/public``.

.. _git-clone:

Using git clone
---------------

You can also install Imbo directly via git, and then use Composer to install the dependencies:

.. code-block:: bash

    mkdir /path/to/install; cd /path/to/install
    git clone git@github.com:imbo/imbo.git
    cd imbo
    curl -s https://getcomposer.org/installer | php
    php composer.phar install -o --no-dev

In this case the correct web server document root would be ``/path/to/install/imbo/public``. Remember to checkout the correct branch after cloning the repository to get the version you want, for instance ``git checkout master``. If you use this method of installation you will have to modify Imbo's ``composer.json`` to install 3rd party libraries. You will also have to place your own configuration file in the same directory as the default Imbo configuration file, which in the above example would be the ``/path/to/install/imbo/config`` directory.

Web server configuration
------------------------

After installing Imbo by using one of the methods mentioned above you will have to configure the web server you want to use. Imbo ships with sample configuration files for `Apache <http://httpd.apache.org/>`_ and `Nginx <http://nginx.org/>`_ that can be used with a few minor adjustments. Both configuration files assume the httpd runs on port 80. If you use `Varnish <https://www.varnish-cache.org/>`_ or some other HTTP accelerator simply change the port number to the port that your httpd listens to.

Apache
~~~~~~

You will need to enable `mod_rewrite <http://httpd.apache.org/docs/current/mod/mod_rewrite.html>`_ if you want to use Imbo with Apache. Below is an example on how to configure Apache for Imbo:

.. literalinclude:: ../../config/imbo.apache.conf.dist
    :language: console

You will need to update ``ServerName`` to match the host name you will use for Imbo. If you want to use several host names you can update the ``ServerAlias`` line as well. You must also update ``DocumentRoot`` and ``Directory`` to point to the ``public`` directory in the Imbo installation. If you want to enable logging update the ``CustomLog`` and ``ErrorLog`` lines. ``RewriteCond`` and ``RewriteRule`` should be left alone.

Nginx
~~~~~

The sample Nginx configuration uses PHP via `FastCGI <http://www.fastcgi.com/>`_:

.. literalinclude:: ../../config/imbo.nginx.conf.dist
    :language: console

You will need to update ``server_name`` to match the host name you will use for Imbo. If you want to use several host names simply put several host names on that line. ``root`` must point to the ``public`` directory in the Imbo installation. If you want to enable logging update the ``error_log`` and ``access_log`` lines. You must also update the ``fastcgi_param SCRIPT_FILENAME`` line to point to the ``public/index.php`` file in the Imbo installation.

Varnish
~~~~~~~

Imbo strives to follow the `HTTP Protocol <http://www.ietf.org/rfc/rfc2616.txt>`_, and can because of this easily leverage `Varnish <https://www.varnish-cache.org/>`_.

The only required configuration you need in your `VCL <https://www.varnish-cache.org/docs/3.0/reference/vcl.html>`_ is a default backend:

.. code-block:: console

    backend default {
        .host = "127.0.0.1";
        .port = "81";
    }

where ``.host`` and ``.port`` is where Varnish can reach your web server.

If you use the same host name (or a sub-domain) for your Imbo installation as other services, that in turn uses `Cookies <http://en.wikipedia.org/wiki/HTTP_cookie>`_, you might want the VCL to ignore these Cookies for the requests made against your Imbo installation (unless you have implemented event listeners for Imbo that uses Cookies). To achieve this you can put the following snippet into your VCL file:

.. code-block:: console

    sub vcl_recv {
        if (req.http.host == "imbo.example.com") {
            unset req.http.Cookie;
        }
    }

or, if you have Imbo installed in some path:

.. code-block:: console

    sub vcl_recv {
        if (req.http.host ~ "^(www.)?example.com$" && req.url ~ "^/imbo/") {
            unset req.http.Cookie;
        }
    }

if your Imbo installation is available on ``[www.]example.com/imbo``.

Database setup
--------------

If you choose to use a RDMS to store data in, you will need to manually create a database, a user and the tables Imbo stores information in. Below you will find schema's for different RDMS's. You will find information regarding how to authenticate against the RDMS of you choice in the :ref:`configuration` topic.

MySQL
~~~~~

.. code-block:: sql

    CREATE TABLE IF NOT EXISTS `imageinfo` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `publicKey` varchar(255) COLLATE utf8_danish_ci NOT NULL,
        `imageIdentifier` char(32) COLLATE utf8_danish_ci NOT NULL,
        `size` int(10) unsigned NOT NULL,
        `extension` varchar(5) COLLATE utf8_danish_ci NOT NULL,
        `mime` varchar(20) COLLATE utf8_danish_ci NOT NULL,
        `added` int(10) unsigned NOT NULL,
        `updated` int(10) unsigned NOT NULL,
        `width` int(10) unsigned NOT NULL,
        `height` int(10) unsigned NOT NULL,
        `checksum` char(32) COLLATE utf8_danish_ci NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `image` (`publicKey`,`imageIdentifier`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci AUTO_INCREMENT=1 ;

    CREATE TABLE IF NOT EXISTS `metadata` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `imageId` int(10) unsigned NOT NULL,
        `tagName` varchar(255) COLLATE utf8_danish_ci NOT NULL,
        `tagValue` varchar(255) COLLATE utf8_danish_ci NOT NULL,
        PRIMARY KEY (`id`),
        KEY `imageId` (`imageId`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci AUTO_INCREMENT=1 ;

The following table is only needed if you plan on storing the actual images themselves in MySQL:

.. code-block:: sql

    CREATE TABLE IF NOT EXISTS `storage_images` (
        `publicKey` varchar(255) COLLATE utf8_danish_ci NOT NULL,
        `imageIdentifier` char(32) COLLATE utf8_danish_ci NOT NULL,
        `data` blob NOT NULL,
        `updated` int(10) unsigned NOT NULL,
        PRIMARY KEY (`publicKey`,`imageIdentifier`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

SQLite
~~~~~~

.. code-block:: sql

    CREATE TABLE IF NOT EXISTS imageinfo (
        id INTEGER PRIMARY KEY NOT NULL,
        publicKey TEXT NOT NULL,
        imageIdentifier TEXT NOT NULL,
        size INTEGER NOT NULL,
        extension TEXT NOT NULL,
        mime TEXT NOT NULL,
        added INTEGER NOT NULL,
        updated INTEGER NOT NULL,
        width INTEGER NOT NULL,
        height INTEGER NOT NULL,
        checksum TEXT NOT NULL,
        UNIQUE (publicKey,imageIdentifier)
    )

    CREATE TABLE IF NOT EXISTS metadata (
        id INTEGER PRIMARY KEY NOT NULL,
        imageId KEY INTEGER NOT NULL,
        tagName TEXT NOT NULL,
        tagValue TEXT NOT NULL
    )

The following table is only needed if you plan on storing the actual images themselves in SQLite:

.. code-block:: sql

    CREATE TABLE storage_images (
        publicKey TEXT NOT NULL,
        imageIdentifier TEXT NOT NULL,
        data BLOB NOT NULL,
        updated INTEGER NOT NULL,
        PRIMARY KEY (publicKey,imageIdentifier)
    )
