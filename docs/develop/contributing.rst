.. _contributing:

Contributing to Imbo
====================

Imbo is an open source project licensed with the `MIT license <https://opensource.org/license/MIT>`_. All contributions must be sent as a pull request on GitHub. Use a descriptive name for your branch, and remember to send the pull request against the ``main`` branch.

If you have found a bug in Imbo, please leave an issue in the `issue tracker <https://github.com/imbo/imbo/issues>`_.

When contributing to Imbo (or any of the other related packages) there are some guidelines you should follow.

Coding standard
---------------

Imbo has a coding standard that is defined as a `PHP-CS-Fixer <https://github.com/PHP-CS-Fixer/PHP-CS-Fixer>`_ standard. The standard is `available on GitHub <https://github.com/imbo/imbo-coding-standard>`_ and is required in the imbo/imbo package. Browse existing code to understand the general look and feel.

There is a composer script that can be used to automatically fix coding standard issues:

.. code-block:: console

    composer run cs # or composer run cs:fix to automatically fix issues

Tests
-----

When introducing new features you are required to add tests. Unit/integration tests (`PHPUnit <https://github.com/sebastianbergmann/phpunit/>`_) and/or `Behat <https://behat.org/>`_ scenarios is sufficient. To run the PHPUnit test suite you can execute the following command in the project root directory after installing Imbo:

.. code-block:: console

    composer run test:unit

For the Behat test suite you can the following command:

.. code-block:: console

    composer run test:integration

Before you trigger the integration tests you need to have a running Imbo instance, and some services running. You can use the provided `Docker Compose <https://docs.docker.com/compose/>`_ setup for this. Start the development server with:

.. code-block:: console

    composer run dev

and the services needed for the integration tests with:

.. code-block:: console

    docker compose up -d

If you find a bug that you want to fix please add a test first that confirms the bug, and then fix the bug, making the newly added test pass.

Documentation
-------------

End user documentation (what you are currently reading) is written using `Sphinx <https://sphinx-doc.org/>`_ and is located in the ``docs/`` directory in the project root. To generate the HTML version of the docs you can execute the following command:

.. code-block:: console

    composer run docs

This task also includes a spell checking stage.

Pull requests on GitHub
-----------------------

If you want to send a pull request, please do so from a publicly available fork of Imbo, using a feature branch with a self descriptive name. The pull request should be sent to the ``main`` branch. If your pull request is fixing an open issue from `the issue tracker <https://github.com/imbo/imbo/issues>`_ your branch can be named after the issue number, for instance ``issue-312``.
