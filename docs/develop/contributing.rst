.. _contributing:

Contributing to Imbo
====================

Imbo is an open source project licensed with the `MIT license <https://opensource.org/license/MIT>`_. All contributions should ideally be sent in form of a pull request on GitHub. Please use features branches with descriptive names, and remember to send the pull request against the ``develop`` branch.

If you have found a bug in Imbo, please leave an issue in the `issue tracker <https://github.com/imbo/imbo/issues>`_.

Requirements
------------

When contributing to Imbo (or any of the other related packages) there are some guidelines you should follow.

Coding standard
+++++++++++++++

Imbo has a coding standard that is partially defined as a `PHP Code Sniffer <http://pear.php.net/package/PHP_CodeSniffer>`_ standard. The standard is `available on GitHub <https://github.com/imbo/imbo-codesniffer>`_ and is installable via `PEAR <http://pear.php.net>`_. There are some details that might not be covered by the standard, so if you send a PR you might notice some nitpicking from my part regarding stuff not covered by the standard. Browse existing code to understand the general look and feel.

Tests
+++++

When introducing new features you are required to add tests. Unit/integration tests (`PHPUnit <https://github.com/sebastianbergmann/phpunit/>`_) and/or `Behat <http://behat.org/>`_ scenarios is sufficient. To run the PHPUnit test suite you can execute the following command in the project root directory after installing Imbo:

.. code-block:: console

    composer test-phpunit

For the Behat test suite you can the following command:

.. code-block:: console

    composer test-behat

If you want to run both suites you can simply run:

.. code-block:: console

    composer test

If you find a bug that you want to fix please add a test first that confirms the bug, and then fix the bug, making the newly added test pass.

Documentation
+++++++++++++

API documentation is written using `phpDocumentor <http://www.phpdoc.org/>`_, and can be generated via a composer script:

.. code-block:: console

    composer qa-phpdoc

End user documentation (the ones you are reading now) is written using `Sphinx <http://sphinx-doc.org/>`_ and is located in the ``docs/`` directory in the project root. To generate the HTML version of the docs you can execute the following command:

.. code-block:: console

    composer docs

This task also includes a spell checking stage.

Pull requests on GitHub
+++++++++++++++++++++++

If you want to send a pull request, please do so from a publicly available fork of Imbo, using a feature branch with a self descriptive name. The pull request should be sent to the ``develop`` branch. If your pull request is fixing an open issue from `the issue tracker <https://github.com/imbo/imbo/issues>`_ your branch can be named after the issue number, for instance ``issue-312``.
