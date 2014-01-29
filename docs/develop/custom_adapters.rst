Implement your own database and/or storage adapter
==================================================

If the adapters shipped with Imbo does not fit your needs you can implement your own set of database and/or storage adapters and have Imbo use them pretty easily. A set of interfaces exists for you to implement, and then all that's left to do is to enable the adapters in your configuration file. See the :ref:`Database confguration <database-configuration>` and :ref:`Storage configuration <storage-configuration>` sections for more information on how to enable different adapters in the configuration.

Custom database adapters must implement the ``Imbo\Database\DatabaseInterface`` interface, and custom storage adapters must implement the ``Imbo\Storage\StorageInterface`` interface.

If you implement an adapter that you think should be a part of Imbo feel free to send a pull request on `GitHub <https://github.com/imbo/imbo>`_.
