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

Metadata keys
+++++++++++++

Prior to Imbo-1.2.0 metadata keys could not contain ``::`` if you used the :ref:`Doctrine database adapter <doctrine-database-adapter>`. From Imbo-1.2.0 this is now true regardless of the adapter you are using. Two other rules have also been added:

* Keys can not contain ``.`` (``foo.bar`` for instance). This is a limitation in MongoDB, and to make it easier for users of Imbo to port data between back-ends it will deny this for all adapters.
* Keys can not start with ``$`` (``$foo`` for instance). This is because of the DSL used by the :ref:`metadata queries <metadata-query>`, added to Imbo-1.2.0.

If you are using the MongoDB adapter, and have keys that contain ``::`` you are encouraged to change these into something else. Likewise, if you are using the Doctrine adapter, and have keys that start with ``$`` or contain a ``.`` you should change these as well for metadata search compatibility.

Metadata queries
++++++++++++++++

Imbo-1.2.0 introduces a new metadata query feature that lets you search for images by querying the metadata attached to the images. Read more about the feature in the :ref:`metadata-query` section about the feature itself.

If you have added metadata to images prior to upgrading to Imbo-1.2.0 **and** use the :ref:`MongoDB database adapter <mongodb-database-adapter>` you will need to update some metadata in the collection used by Imbo. The :doc:`command line script <cli>` that ships with Imbo can be used to convert the data for you, more specifically the :ref:`generate-normalized-metadata <cli-generate-normalized-metadata>` command. If you use the :ref:`Doctrine database adapter <doctrine-database-adapter>` you do not need to worry about this.

Original checksum
+++++++++++++++++

Imbo-1.2.0 includes a new feature that lets you filter images based on the original checksum of the image when querying the :ref:`images resource <images-resource>`. For this to work you need to add a field to your database. You can also populate this field for all images if you want, but this is not required. If you have event listeners that update incoming images, the values already stored in the database under the ``checksum`` field (which is used to populate the ``originalChecksum`` field in the following examples) might not be the checksum of the original image. If you don't have such event listeners added to your configuration you should be able to update the data as explained below and end up with 100% correct results.

Doctrine
~~~~~~~~

If you use the :ref:`Doctrine database adapter <doctrine-database-adapter>` you'll need to add the new ``originalChecksum`` field to the table. The field has also been added to the :ref:`database-setup` section. The field should be added while there are no write operations pending, as a write could fail before upgrading Imbo itself.

.. code-block:: sql

    ALTER TABLE imageinfo ADD COLUMN `originalChecksum` char(32) COLLATE utf8_danish_ci NOT NULL;

When you have added the field to your database you can run the following query to update all rows in the database:

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

Response to metadata write operations
+++++++++++++++++++++++++++++++++++++

Versions prior to 1.2.0 contained the image identifier in the response to ``HTTP POST/PUT/DELETE`` against the :ref:`metadata resource <metadata-resource>`. Starting from Imbo-1.2.0 the response to these requests will contain the metadata attached to the image instead. Read more about the different responses in the :ref:`metadata resource <metadata-resource>` section.
