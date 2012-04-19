Custom database drivers
=======================

If you wish to implement your own database driver you are free to do so. The only requirement is that you implement the ``Imbo\Database\DatabaseInterface`` interface that comes with Imbo. Below is the complete interface with comments:

.. literalinclude:: ../../library/Imbo/Database/DatabaseInterface.php
    :language: php
    :linenos:

Have a look at the existing implementations of this interface for more details. If you implement a driver that you think should be a part of Imbo feel free to send a pull request to the `project over at GitHub`_.

.. _project over at GitHub: https://github.com/imbo/imbo
