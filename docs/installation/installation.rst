.. _installation:

Installation
============

To install Imbo on the server you should use `Composer`_. Create a ``composer.json`` file for your installation, and install Imbo and optional 3rd party plug-ins via the ``composer`` binary. First, you will need the following directory structure::

    /path/to/install/composer.json
    /path/to/install/config/

where the ``composer.json`` file can contain:

.. code-block:: json

    {
      "name": "yourname/imbo",
      "require": {
        "imbo/imbo": "^3.0"
      }
    }

and the ``config/`` directory contains one or more configuration files that will be merged with the :ref:`default configuration <configuration>`. Imbo will load **all** ``.php`` files in this directory, and the ones returning an array will be used as configuration.

If you want to install 3rd party plug-ins and/or for instance the Doctrine DBAL library and the MongoDB PHP library simply add these to the ``require`` object in your ``composer.json`` file:

.. code-block:: json

    {
      "name": "yourname/imbo",
      "require": {
        "imbo/imbo": "^3.0",
        "rexxars/imbo-hipsta": "dev-master",
        "imbo/imbo": "^3.0",
        "doctrine/dbal": "^2.5",
        "mongodb/mongodb": "^1.1"
      }
    }

If some of the 3rd party plug-ins provide configuration files, you can link to these in the ``config/`` directory to have Imbo automatically load them:

.. code-block:: bash

    cd /path/to/install/config
    ln -s ../vendor/rexxars/imbo-hipsta/config/config.php 01-imbo-hipsta.php

To be able to control the order that Imbo uses when loading the configuration files you should prefix them with a number, like ``01`` in the example above. Lower numbers will be loaded first, meaning that configuration files with higher numbers will override settings set in configuration files with a lower number.

When you have created the ``composer.json`` file you can install Imbo with Composer:

.. code-block:: bash

    composer install -o --no-dev

After composer has finished installing Imbo and optional dependencies the Imbo installation will reside in ``/path/to/install/vendor/imbo/imbo``. The correct web server document root in this case would be ``/path/to/install/vendor/imbo/imbo/public``.

If you later want to update Imbo you can bump the version number you have specified in ``composer.json`` and run:

.. code-block:: bash

    composer update -o --no-dev

Regarding the Imbo version you are about to install you can use ``dev-master`` for the latest released version, or you can use a specific version if you want to (recommended). Head over to `Imbo on Packagist`_ to see the available versions. If you're more of a YOLO type of person you can use ``dev-develop`` for the latest development version. If you choose to use the ``dev-develop`` branch, expect things to break from time to time.

Imbo strives to keep full :abbr:`BC (Backwards Compatibility)` in minor and patch releases, but breaking changes can occur. The most secure way to install one or more Imbo servers is to specify a specific version (for instance ``3.0.0``, or by using `semver`_: ``^3.0``) in your ``composer.json`` file. Read the `Imbo ChangeLog`_ and the :doc:`upgrading` chapter before doing an upgrade.

Web server configuration
------------------------

After installing Imbo you will have to configure the web server you want to use. Imbo ships with sample configuration files for `Apache`_, `Nginx`_ and `Lighttpd`_ that can be used with a few minor adjustments. All configuration files assume the httpd runs on port 80. If you use `Varnish`_ or some other HTTP accelerator simply change the port number to the port that your httpd listens to.

Apache
~~~~~~

You will need to enable `mod_rewrite`_ if you want to use Imbo with Apache. Below is an example on how to configure Apache for Imbo:

.. literalinclude:: ../../config/imbo.apache.conf.dist

You will need to update ``ServerName`` to match the host name you will use for Imbo. If you want to use several host names you can update the ``ServerAlias`` line as well. You must also update ``DocumentRoot`` and ``Directory`` to point to the ``public`` directory in the Imbo installation. If you want to enable logging update the ``CustomLog`` and ``ErrorLog`` lines. ``RewriteCond`` and ``RewriteRule`` should be left alone.

Nginx
~~~~~

Below is an example on how to configure Nginx for Imbo. This example uses PHP via `FastCGI`_:

.. literalinclude:: ../../config/imbo.nginx.conf.dist

You will need to update ``server_name`` to match the host name you will use for Imbo. If you want to use several host names simply put several host names on that line. ``root`` must point to the ``public`` directory in the Imbo installation. If you want to enable logging update the ``error_log`` and ``access_log`` lines. You must also update the ``fastcgi_param SCRIPT_FILENAME`` line to point to the ``public/index.php`` file in the Imbo installation.

Lighttpd
~~~~~~~~

Below is an example on how to configure Lighttpd for Imbo. Running PHP through FastCGI is recommended (not covered here).

.. literalinclude:: ../../config/imbo.lighttpd.conf.dist

You will need to set the correct host name(s) used with ``$HTTP["host"]`` and update the ``server.document-root`` to point to the correct path. If you want to enable logging remove the comments on the lines with ``server.errorlog`` and ``accesslog.filename`` and set the correct paths. If you want to specify a custom access log path you will need to enable the ``mod_accesslog`` module.

This example requires the ``mod_rewrite`` module to be loaded.

Varnish
~~~~~~~

Imbo strives to follow the `HTTP Protocol`_, and can because of this easily leverage `Varnish`_.

The only required configuration you need in your `VCL`_ is a default backend:

.. code-block:: console

    backend default {
        .host = "127.0.0.1";
        .port = "81";
    }

where ``.host`` and ``.port`` is where Varnish can reach your web server.

If you use the same host name (or a sub-domain) for your Imbo installation as other services, that in turn uses `Cookies`_, you might want the VCL to ignore these Cookies for the requests made against your Imbo installation (unless you have implemented event listeners for Imbo that uses Cookies). To achieve this you can put the following snippet into your VCL file:

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

.. _database-setup:

Database setup
--------------

If you choose to use a RDBMS to store data in, you will need to manually create a database, a user and the tables Imbo stores information in. Below you will find schemas for different RDBMSs. You will find information regarding how to authenticate against the RDBMS of you choice in the :ref:`configuration` topic.

MySQL
~~~~~

.. literalinclude:: ../../setup/doctrine.mysql.sql
    :language: sql

SQLite
~~~~~~

.. literalinclude:: ../../setup/doctrine.sqlite.sql
    :language: sql

.. _Composer: https://getcomposer.org
.. _Imbo on Packagist: https://packagist.org/packages/imbo/imbo
.. _semver: http://semver.org/
.. _Imbo ChangeLog: https://github.com/imbo/imbo/blob/develop/ChangeLog.markdown
.. _Apache: https://httpd.apache.org/
.. _Nginx: https://nginx.org/
.. _Lighttpd: https://www.lighttpd.net/
.. _Varnish: https://www.varnish-cache.org/
.. _mod_rewrite: https://httpd.apache.org/docs/current/mod/mod_rewrite.html
.. _FastCGI: https://www.fastcgi.com/
.. _HTTP Protocol: https://www.ietf.org/rfc/rfc2616.txt
.. _VCL: https://www.varnish-cache.org/docs/3.0/reference/vcl.html
.. _Cookies: https://en.wikipedia.org/wiki/HTTP_cookie
