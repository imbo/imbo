Command line tool
=================

Imbo ships with a command line tool which can aid you when setting up Imbo and keeping it up to date. This chapter lists all available commands and what they are capable of.

The binary can be found in one of two places, depending on the :doc:`installation` method you chose. If you installed Imbo via Composer, the binary can be found in ``vendor/bin/imbo``, and if you used git clone as the installation method it can be found in ``bin/imbo``.

.. contents::
    :local:
    :depth: 1

.. _cli-generate-normalized-metadata:

Generate normalized metadata (MongoDB) - ``generate-normalized-metadata``
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

This command can be used to generate normalized metadata in MongoDB in case you have added metadata prior to upgrading to Imbo-1.2.0 **and** use the MongoDB database adapter. If you use the Doctrine adapter you don't need to run this command. Running the command multiple times is safe, but should not be needed.

The command supports three optional options:

* ``--server`` The server to connect to, defaults to ``mongodb://localhost:27017``.
* ``--database`` The database the data exists in, defaults to ``imbo``.
* ``--collection`` The collection of the data, defaults to ``images``.

Example:

.. code-block:: console

   ./bin/imbo generate-normalized-metadata --server mongodb://somehost --database my-imbo-database

The command requires the `ext-mongo <http://pecl.php.net/package/mongo>`_ extension to be installed to be able to convert the data. You will also need to confirm the conversion before it starts. Please make a backup of the data before running this command.

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
