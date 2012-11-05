Installation
============

Imbo is still a work in progress and there is no automatic installation yet. Simply clone the repository `available on GitHub`_ or make your own fork. Automatic installation using `PEAR`_ and `composer`_ will be provided later.

.. _available on GitHub: http://github.com/imbo/imbo
.. _PEAR: http://pear.php.net/
.. _composer: http://getcomposer.org/

Web server configuration
------------------------

Imbo ships with a sample configuration files for `Apache`_ and `Nginx`_ that can be used with a few minor adjustments. Both configuration files assumes you run your httpd on port 80. If you use `Varnish`_ or some other HTTP accelerator, simply change the port number to the port that your httpd listens to.

Apache
~~~~~~

You will need to enable `mod_rewrite`_ if you want to use Imbo with Apache.

.. literalinclude:: ../../config/imbo.apache.conf.dist
    :language: console
    :linenos:

Nginx
~~~~~

The sample Nginx configuration uses PHP via `FastCGI`_.

.. literalinclude:: ../../config/imbo.nginx.conf.dist
    :language: console
    :linenos:

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
