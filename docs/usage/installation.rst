Installation
============

The easiest way to install Imbo is to `clone the repository`, and then use `Composer`_ to install the dependencies:

.. code-block:: bash

    $ git clone git@github.com:imbo/imbo.git
    $ cd imbo
    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install

.. _clone the repository: http://github.com/imbo/imbo
.. _Composer: http://getcomposer.org/

After installing the PHP files you will need to configure your web server.

Web server configuration
------------------------

Imbo ships with sample configuration files for `Apache`_ and `Nginx`_ that can be used with a few minor adjustments. Both configuration files assumes you run your httpd on port 80. If you use `Varnish`_ or some other HTTP accelerator, simply change the port number to the port that your httpd listens to.

Apache
~~~~~~

You will need to enable `mod_rewrite`_ if you want to use Imbo with Apache.

.. literalinclude:: ../../config/imbo.apache.conf.dist
    :language: console
    :linenos:

You will need to update ``ServerName`` to match the host name you will use for Imbo. If you want to use several host names you can update the ``ServerAlias`` line as well. You must also update ``DocumentRoot`` and ``Directory`` to point to the ``public`` directory in the Imbo installation. If you want to enable logging update the ``CustomLog`` and ``ErrorLog`` lines.

Nginx
~~~~~

The sample Nginx configuration uses PHP via `FastCGI`_.

.. literalinclude:: ../../config/imbo.nginx.conf.dist
    :language: console
    :linenos:

You will need to update ``server_name`` to match the host name you will use for Imbo. If you want to use several host names simply put several host names on that line. ``root`` must point to the ``public`` directory in the Imbo installation. If you want to enable logging update the ``error_log`` and ``access_log`` lines. You must also update the ``fastcgi_param SCRIPT_FILENAME`` line to point to the ``public/index.php`` file in the Imbo installation.

.. _Apache: http://httpd.apache.org/
.. _mod_rewrite: http://httpd.apache.org/docs/current/mod/mod_rewrite.html
.. _Nginx: http://nginx.org/
.. _Varnish: https://www.varnish-cache.org/
.. _FastCGI: http://www.fastcgi.com/

Varnish
-------

Imbo strives to follow the `HTTP Protocol`_, and can because of this easily leverage `Varnish`_.

.. _HTTP Protocol: http://www.ietf.org/rfc/rfc2616.txt
.. _Varnish: https://www.varnish-cache.org/

The only required configuration you need in your `VCL`_ is a default backend:

.. _VCL: https://www.varnish-cache.org/docs/3.0/reference/vcl.html

.. code-block:: console

    backend default {
        .host = "127.0.0.1";
        .port = "81";
    }

where ``.host`` and ``.port`` is where Varnish can reach your web server.

If you use the same host name (or a sub-domain) for your Imbo installation as other services, that in turn uses `Cookies`_, you might want the VCL to ignore these Cookies for the requests made against your Imbo installation (unless you have implemented event listeners for Imbo that uses Cookies). To achieve this you can put the following snippet into your VCL file:

.. _Cookies: http://en.wikipedia.org/wiki/HTTP_cookie

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

if you have Imbo installed in ``example.com/imbo``.
