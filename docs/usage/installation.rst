Installation
============

Imbo is still a work in progress and there is no automatic installation yet. Simply clone the repository `available on GitHub`_ or make your own fork. Automatic installation using `PEAR`_ and `composer`_ will be provided later.

.. _available on GitHub: http://github.com/imbo/imbo
.. _PEAR: http://pear.php.net/
.. _composer: http://getcomposer.org/

Apache configuration
--------------------
Imbo ships with a sample Apache configuration file that can be used with a few minor adjustments:

.. literalinclude:: ../../config/imbo.conf.dist
    :language: console
    :linenos:

You will need to update some settings to suit your needs. The last part of the configuration is required, so make sure to enable `mod_rewrite`_.

If you want to leverage multiple host names you can uncomment the ``ServerAlias`` line and add your host names.

.. _mod_rewrite: http://httpd.apache.org/docs/current/mod/mod_rewrite.html
