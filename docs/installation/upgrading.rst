.. _upgrading:

Upgrading Imbo
==============

Depending on the :ref:`installation method <installation>` you chose, upgrading Imbo can be done quite easily. If you went for the :ref:`recommended version <using-composer>` you will only have to bump the version number in your custom ``composer.json`` file and run ``composer update``.

If you did a :ref:`git clone <git-clone>` you could simply do a ``git pull`` to update your working copy.

From time to time Imbo will introduce new features or fix bugs that might require you to update the contents of the database you choose to use. This chapter will contain all information you need to keep your installation up to date. Each of the following sections include the necessary steps you need to execute when upgrading to the different versions.

Imbo-1.2.0
----------

Below are the changes you need to be aware of when upgrading to Imbo-1.2.0.

.. contents::
    :local:
    :depth: 2

Response to metadata write operations
+++++++++++++++++++++++++++++++++++++

Versions prior to 1.2.0 contained the image identifier in the response to ``HTTP POST/PUT/DELETE`` against the :ref:`metadata resource <metadata-resource>`. Starting from Imbo-1.2.0 the response to these requests will contain the metadata attached to the image instead. Read more about the different responses in the :ref:`metadata resource <metadata-resource>` section.

Original checksum
+++++++++++++++++

Imbo-1.2.0 includes a new feature that lets you filter images based on the original checksum of the image when querying the :ref:`images resource <images-resource>`. For this to work you need to add a field to your database. You can also populate this field for all images if you want, but this is not required. If you have event listeners that update incoming images, the values already stored in the database under the ``checksum`` field (which is used to populate the ``originalChecksum`` field in the following examples) might not be the checksum of the original image. If you don't have such event listeners added to your configuration you should be able to update the data as explained below and end up with 100% correct results.

Doctrine
~~~~~~~~

If you use the :ref:`Doctrine database adapter <doctrine-database-adapter>` a definition of the ``originalChecksum`` field can be found in the :ref:`database-setup` section. When you have added the field to your database you can run the following query to update all rows in the database:

.. code-block:: sql

    UPDATE `imageinfo` SET `originalChecksum` = `checksum`

This query will simply copy the value of the existing ``checksum`` field over to ``originalChecksum``. If you have a lot of images this operation might take a while.

MongoDB
~~~~~~~

If you use the MongoDB adapter all you need to do is to update all entries in the image collection:

.. code-block:: javascript

    db.image.find().forEach(
        function (elem) {
            db.image.update(
                { _id: elem._id },
                { $set: { originalChecksum: elem.checksum }}
            );
        }
    )

Short image URLs
++++++++++++++++

In versions prior to Imbo-1.2.0 short image URLs were created automatically whenever a user agent requested the image resource (with or without transformations), and sent in the response as the ``X-Imbo-ShortUrl`` header. This no longer done automatically. Refer to the :ref:`shorturls-resource` section for more information on how to generate short URLs from this version on.
