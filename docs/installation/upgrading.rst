.. _upgrading:

Upgrading Imbo
==============

Depending on the :ref:`installation method <installation>` you chose, upgrading Imbo can be done quite easily. If you went for the :ref:`recommended version <using-composer>` you will only have to bump the version number in your custom ``composer.json`` file and run ``composer update``.

If you did a :ref:`git clone <git-clone>` you could simply do a ``git pull`` to update your working copy.

From time to time Imbo will introduce new features or fix bugs that might require you to update the contents of the database you choose to use. This chapter will contain all information you need to keep your installation up to date. Each of the following sections include the necessary steps you need to execute when upgrading to the different versions.

Imbo-2.0.0
----------

Below are the changes you need to be aware of when upgrading to Imbo-2.0.0.

.. contents::
    :local:
    :depth: 2

Public key is an authentication detail
++++++++++++++++++++++++++++++++++++++

Versions prior to 2.0.0 had a 1:1 correlation between what a ``user`` and a ``public key`` was. In 2.0.0, a ``user`` is the entity which images belong to, while a ``public key`` is one part of the authentication scheme. ``Public keys`` each has their own set of permissions, which can grant them access to different resources within different users.

Imbo provides some level of backwards compatibility with the old ``auth`` part of the configuration file, but no longer supports multiple private keys per public key (such as read-only/read+write). If you are using this, Imbo will throw an exception on startup. We recommend moving away from the ``auth`` setup and onto the new :ref:`Access Control adapters <access-control-configuration>` as soon as possible.

Prior to 2.0.0, both the database and HTTP endpoints used a field name of ``publicKey`` to identify the user. Going forward, apart from when working with authentication, ``user`` will be the new field name. This requires some database changes - see below.

Doctrine
~~~~~~~~

If you use the :ref:`Doctrine database adapter <doctrine-database-adapter>` you'll need to rename the ``publicKey`` fields to ``user``. The field has been updated in the :ref:`database-setup` section. The field should be renamed while there are no write operations pending, as a write could fail before upgrading Imbo itself.

Postgres/Other
^^^^^^^^^^^^^^
.. code-block:: sql

    ALTER TABLE imageinfo RENAME COLUMN publicKey to user;
    ALTER TABLE shorturl RENAME COLUMN publicKey to user;
    ALTER TABLE imagevariations RENAME COLUMN publicKey to user;

MySQL
^^^^^
.. code-block:: sql

    ALTER TABLE imageinfo CHANGE `publicKey` `user` varchar(255) COLLATE utf8_danish_ci NOT NULL;
    ALTER TABLE shorturl CHANGE `publicKey` `user` varchar(255) COLLATE utf8_danish_ci NOT NULL;
    ALTER TABLE imagevariations CHANGE `publicKey` `user` varchar(255) COLLATE utf8_danish_ci NOT NULL;


If you use the Doctrine storage adapter for images and/or image variations, you will have to rename fields in those databases too:

Postgres/Other
^^^^^^^^^^^^^^
.. code-block:: sql

    ALTER TABLE storage_images RENAME COLUMN publicKey to user;
    ALTER TABLE storage_image_variations RENAME COLUMN publicKey to user;

MySQL
^^^^^
.. code-block:: sql

    ALTER TABLE storage_images CHANGE `publicKey` `user` varchar(255) COLLATE utf8_danish_ci NOT NULL;
    ALTER TABLE storage_image_variations CHANGE `publicKey` `user` varchar(255) COLLATE utf8_danish_ci NOT NULL;

.. note:: The ``imagevariations`` and ``storage_image_variations`` table might not be present in your database unless you previously upgraded to 1.2.4. In this case, skip the queries affecting those tables and instead follow the instructions specified in the :ref:`database-setup` section.

MongoDB
~~~~~~~

If you use the MongoDB adapter, you will need to rename the ``publicKey`` field with the following queries:

.. code-block:: javascript

    db.image.update({}, { $rename: { 'publicKey': 'user' } }, { multi: true })
    db.shortUrl.update({}, { $rename: { 'publicKey': 'user' } }, { multi: true })
    db.imagevariation.update({}, { $rename: { 'publicKey': 'user' } }, { multi: true })

.. note:: The ``imagevariation`` collection might not be present in your database unless you previously upgraded to 1.2.4. In this case, skip the last query and instead follow the instructions specified in the :ref:`database-setup` section.

GridFS
~~~~~~

If you use the GridFS adapter, you will need to rename the ``publicKey`` field with the following query:

.. code-block:: javascript

    db.fs.files.update({}, { $rename: { 'publicKey': 'user' } }, { multi: true } )

.. note:: The default database names for the GridFS adapters are ``imbo_storage`` and ``imbo_imagevariation_storage``. The query specified should be run on both databases. If the ``imbo_imagevariation_storage`` database does not exist, run the query on ``imbo_storage`` and follow the instructions specified in the :ref:`database-setup` section to create the appropriate indexes for the ``imbo_imagevariation_storage`` database.

Image identifiers are no longer MD5-sums
++++++++++++++++++++++++++++++++++++++++

Previously, Imbo used the MD5 algorithm to generate the image identifier for an image. In Imbo 2.0.0 and onwards, image identifiers are simply randomly generated strings. This means that the same image can exist multiple times within the same user. If this is not what you want, you can check if the image already exists by querying the :ref:`images resource <images-resource>` and specifying the MD5-sum of the image as an ``originalChecksum``-filter. Most Imbo-clients implement this already, as ``imageExists()`` or similar.

To accommodate the new image identifiers and the possibility of future changes in how they are represented, databases should be able to store an image identifier of up to 255 characters. If you are using the :ref:`Doctrine database adapter <doctrine-database-adapter>` with the suggested schema on a MySQL database, this will require some changes:

.. code-block:: sql

    ALTER TABLE imageinfo MODIFY imageIdentifier varchar(255) COLLATE utf8_danish_ci NOT NULL;
    ALTER TABLE shorturl MODIFY imageIdentifier varchar(255) COLLATE utf8_danish_ci NOT NULL;
    ALTER TABLE imagevariations MODIFY imageIdentifier varchar(255) COLLATE utf8_danish_ci NOT NULL;

If you use the Doctrine storage adapter for images and/or image variations, you will have to rename fields in those databases too:

.. code-block:: sql

    ALTER TABLE storage_images MODIFY imageIdentifier varchar(255) COLLATE utf8_danish_ci NOT NULL;
    ALTER TABLE storage_image_variations MODIFY imageIdentifier varchar(255) COLLATE utf8_danish_ci NOT NULL;

.. note:: The ``imagevariations`` and ``storage_image_variations`` table might not be present in your database unless you previously upgraded to 1.2.4. In this case, skip the queries affecting those tables and instead follow the instructions specified in the :ref:`database-setup` section.

Imbo-1.2.4
----------

A new :ref:`Image Variations <image-variations-listener>` event listener was introduced. It is disabled by default, and to use it you will have to configure a database and storage adapter for it - depending on your choice of adapters, you might need to modify your database. See the :ref:`database-setup` section.

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
