Command line tool
=================

Imbo ships with a command line tool which can aid you when setting up Imbo and keeping it up to date. This chapter lists all available commands and what they are capable of.

The binary can be found in one of two places, depending on the :doc:`installation` method you chose. If you installed Imbo via Composer, the binary can be found in ``vendor/bin/imbo``, and if you used git clone as the installation method it can be found in ``bin/imbo``.

.. contents::
    :local:
    :depth: 1

.. _cli-add-public-key:

Add a public key - ``add-public-key``
+++++++++++++++++++++++++++++++++++++

When using a mutable access control adapter (usually meaning it's backed by a database or similar), this command helps you with adding public/private key pairs and associated rules. It's an alternative to using Imbo's public REST API for this purpose, and is the only way to add an initial public key with access to create and modify other public keys.

Example:

.. code-block:: console

    ./bin/imbo add-public-key somePublicKey

The above command will start an interactive session that will guide you through creating a public key with the name ``somePublicKey``, given it does not already exist.

.. _cli-generate-private-key:

Generate a private key - ``generate-private-key``
+++++++++++++++++++++++++++++++++++++++++++++++++

The script that was earlier called ``scripts/generatePrivateKey.php`` is now included in the CLI tool. This commands does not support any arguments.

Example:

.. code-block:: console

    ./bin/imbo generate-private-key

The above command will simply output a secret key that can be used as a private key for an Imbo user.

.. _cli-help:

Help - ``help``
+++++++++++++++

Use this command to get a detailed description of another command along with available arguments and their effect on the command.

Example:

.. code-block:: console

    ./bin/imbo help generate-private-key

The above command will provide a description of the :ref:`generate-private-key <cli-generate-private-key>` command.

.. _cli-list:

List commands - ``list``
++++++++++++++++++++++++

This command can be used to simply list all commands along with their short description. This is the default command that is executed when running ``./bin/imbo`` with no arguments.
