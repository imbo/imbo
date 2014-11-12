.. _contributing:

Contributing to Imbo
====================

Imbo is an open source project licensed with the `MIT license <http://opensource.org/licenses/MIT>`_. All contributions should ideally be sent in form of a pull request on GitHub. Please use features branches with descriptive names, and remember to send the pull request against the ``develop`` branch.

If you have found a bug in Imbo, please leave an issue in the `issue tracker <https://github.com/imbo/imbo/issues>`_.

Build script
------------

Imbo uses `Rake <http://rake.rubyforge.org/>`_ for building, and if you have Rake installed you can simply run the ``rake`` command after cloning Imbo to run the complete build. You might need to install some additional tools for the whole build to complete successfully. If you need help getting the build script to work with no errors drop by the ``#imbo`` channel on IRC (Freenode) or simply add an issue in the issue tracker on GitHub.

Running the complete suite is not necessary for all contributions. If you skip the build script and simply want to get Imbo up and running for contributing you can run the following commands in the directory where you cloned Imbo:

.. code-block:: console

    curl -s https://getcomposer.org/installer | php
    php composer.phar install

Remember to **not** include the ``--no-dev`` argument to composer. If you include that argument the development requirements will not be installed.

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

    ./vendor/bin/phpunit -c tests/phpunit

If you want to generate code coverage as well you can run the test suite by using a Rake task:

.. code-block:: console

    rake phpunit

For the Behat test suite you can run similar commands:

.. code-block:: console

    ./vendor/bin/behat --strict --profile no-cc --config tests/behat/behat.yml

to skip code coverage, or

.. code-block:: console

    rake behat

for code coverage of the Behat tests. If you want to run both suites and collect code coverage you can execute:

.. code-block:: console

    rake test

Code coverage is located in ``build/coverage`` and ``build/behat-coverage`` respectively.

If you find a bug that you want to fix please add a test first that confirms the bug, and then fix the bug, making the newly added test pass.

Documentation
+++++++++++++

API documentation is written using `phpDocumentor <http://www.phpdoc.org/>`_, and can be generated via a Rake task:

.. code-block:: console

    rake apidocs

End user documentation (the ones you are reading now) is written using `Sphinx <http://sphinx-doc.org/>`_ and is located in the ``docs/`` directory in the project root. To generate the HTML version of the docs you can execute the following command:

.. code-block:: console

    rake readthedocs

This task also includes a spell checking stage.

Pull requests on GitHub
+++++++++++++++++++++++

If you want to send a pull request, please do so from a publicly available fork of Imbo, using a feature branch with a self descriptive name. The pull request should be sent to the ``develop`` branch. If your pull request is fixing an open issue from `the issue tracker <https://github.com/imbo/imbo/issues>`_ your branch can be named after the issue number, for instance ``issue-312``.
