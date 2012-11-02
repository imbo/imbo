Installation
============

Imbo is still a work in progress and there is no automatic installation yet. Simply clone the repository `available on GitHub`_ or make your own fork. Automatic installation using `PEAR`_ and `composer`_ will be provided later.

.. _available on GitHub: http://github.com/imbo/imbo
.. _PEAR: http://pear.php.net/
.. _composer: http://getcomposer.org/

Web server configuration
------------------------
Imbo ships with a sample configuration files for `Apache`_ and `Nginx`_ that can be used with a few minor adjustments:

Apache
~~~~~~
.. literalinclude:: ../../config/imbo.apache.conf.dist
    :language: console
    :linenos:

Nginx
~~~~~
.. literalinclude:: ../../config/imbo.nginx.conf.dist
    :language: console
    :linenos:

.. _Apache: http://httpd.apache.org/
.. _Nginx: http://nginx.org/

