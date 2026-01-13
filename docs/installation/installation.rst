.. _installation:

Installation
============

To install Imbo on the server you should use `Composer`_. Create a directory for your installation, and run the following command:

.. code-block:: bash

    export PROJECT_DIR=/path/to/install
    mkdir -p $PROJECT_DIR/config
    cd $PROJECT_DIR
    composer require imbo/imbo ^3.0

Adjust the generated ``composer.json`` to your needs.

The ``config/`` directory will contain one or more configuration files that will be merged with the :ref:`default configuration <configuration>`. Imbo will load **all** ``.php`` files in this directory (in ascending lexicographical order), and the ones returning an array will be used as configuration.

If you want to install 3rd party plug-ins and/or for instance the MongoDB PHP library simply require them using composer:

.. code-block:: bash

    cd $PROJECT_DIR
    composer require mongodb/mongodb

After installing Imbo and optional dependencies the Imbo installation will reside in ``$PROJECT_DIR/vendor/imbo/imbo``. The correct web server document root in this case would be ``/path/to/install/vendor/imbo/imbo/public``.

If you later want to update Imbo you can bump the version number you have specified in ``composer.json`` and run:

.. code-block:: bash

    composer update -o

Regarding the Imbo version you are about to install you can use ``dev-main`` for the latest released version, or you can use a specific version if you want to, which is strongly recommended. Head over to `Imbo on Packagist`_ to see the available versions.

Imbo strives to keep full :abbr:`BC (Backwards Compatibility)` in minor and patch releases, but breaking changes can occur. The most secure way to install one or more Imbo servers is to specify a specific version (for instance ``3.0.0``, or by using `semver`_: ``^3.0``) in your ``composer.json`` file. Read the `Imbo ChangeLog`_ and the :doc:`upgrading` chapter before doing an upgrade.

Web server configuration
------------------------

After installing Imbo you will have to configure the web server you want to use. Imbo ships with sample configuration files for `Apache`_ and `Nginx`_ that can be used with a few minor adjustments. All configuration files assume the httpd runs on port 80. If you use `Varnish`_ or some other HTTP accelerator simply change the port number to the port that your httpd listens to.

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

If you choose to use a RDBMS to store data in, you will need to manually create a database, a user and the tables Imbo stores information in. You will find information regarding how to authenticate against the RDBMS of you choice in the :ref:`configuration` topic.

.. _Composer: https://getcomposer.org
.. _Imbo on Packagist: https://packagist.org/packages/imbo/imbo
.. _semver: http://semver.org/
.. _Imbo ChangeLog: https://github.com/imbo/imbo/blob/develop/ChangeLog.markdown
.. _Apache: https://httpd.apache.org/
.. _Nginx: https://nginx.org/
.. _Varnish: https://www.varnish-cache.org/
.. _mod_rewrite: https://httpd.apache.org/docs/current/mod/mod_rewrite.html
.. _FastCGI: https://www.fastcgi.com/
.. _HTTP Protocol: https://www.ietf.org/rfc/rfc2616.txt
.. _VCL: https://www.varnish-cache.org/docs/3.0/reference/vcl.html
.. _Cookies: https://en.wikipedia.org/wiki/HTTP_cookie
