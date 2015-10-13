Imbo's API
==========

In this chapter you will learn more about how Imbo's API works, and how you as a user are able to read from and write to Imbo. Most examples listed in this chapter will use `cURL <http://curl.haxx.se/>`_, so while playing around with the API it's encouraged to have cURL easily available. For the sake of simplicity the access tokens and authentication information is not used in the examples. See the :ref:`access-tokens` and :ref:`signing-write-requests` sections for more information regarding this.

Resources/endpoints
-------------------

In this section you will find information on the different resources Imbo's RESTful API expose, along with their capabilities:

.. contents:: Available resources
    :local:
    :depth: 1

.. _index-resource:

Index resource - ``/``
++++++++++++++++++++++

The index resource shows the version of the Imbo installation along with some external URLs for Imbo-related information, and some internal URLs for the available endpoints.

.. code-block:: bash

    curl -H"Accept: application/json" http://imbo

results in:

.. code-block:: javascript

    {
      "version": "dev",
      "urls": {
        "site": "http://www.imbo-project.org",
        "source": "https://github.com/imbo/imbo",
        "issues": "https://github.com/imbo/imbo/issues",
        "docs": "http://docs.imbo-project.org"
      },
      "endpoints": {
        "status": "http://imbo/status",
        "stats": "http://imbo/stats",
        "user": "http://imbo/users/{user}",
        "images": "http://imbo/users/{user}/images",
        "image": "http://imbo/users/{user}/images/{imageIdentifier}",
        "globalShortImageUrl": "http://imbo/s/{id}",
        "metadata": "http://imbo/users/{user}/images/{imageIdentifier}/metadata",
        "shortImageUrls": "http://imbo/users/{user}/images/{imageIdentifier}/shorturls",
        "shortImageUrl": "http://imbo/users/{user}/images/{imageIdentifier}/shorturls/{id}"
      }
    }

This resource does not support any extensions in the URI, so you will need to use the ``Accept`` header to fetch different representations of the data.

The index resource does not require any authentication per default.

**Typical response codes:**

* 200 Hell Yeah

.. note:: The index resource is not cache-able.

.. _stats-resource:

Stats resource - ``/stats``
+++++++++++++++++++++++++++

Imbo provides an endpoint for fetching simple statistics about the data stored in Imbo.

.. code-block:: bash

    curl http://imbo/stats.json

results in:

.. code-block:: javascript

    {
      "numImages": 12,
      "numUsers": 2,
      "numBytes": 3898294
      "custom": {}
    }

if the client making the request is allowed access.

Access control
~~~~~~~~~~~~~~

The access control for the stats endpoint is controlled by an event listener, which is enabled per default, and only allows connections from ``127.0.0.1`` (IPv4) and ``::1`` (IPv6). Read more about how to configure this event listener in the :ref:`Stats access event listener <stats-access-event-listener>` section.

Custom statistics
~~~~~~~~~~~~~~~~~

The stats resource enables users to attach custom statistics via event listeners by using the data model as a regular associative array. The following example attaches a simple event listener in the configuration file that populates some custom data in the statistics model:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'customStats' => [
                'events' => ['stats.get'],
                'callback' => function($event) {
                    // Fetch the model from the response
                    $model = $event->getResponse()->getModel();

                    // Set some values
                    $model['someValue'] = 123;
                    $model['someOtherValue'] = [
                        'foo' => 'bar',
                    ];
                    $model['someList'] = [1, 2, 3];
                }
            ],
        ],

        // ...
    ];

When requesting the stats endpoint, the output will look like this:

.. code-block:: javascript

    {
      "users": {
        "someuser": {
          "numImages": 11,
          "numBytes": 3817197
        },
        "someotheruser": {
          "numImages": 1,
          "numBytes": 81097
        }
      },
      "total": {
        "numImages": 12,
        "numUsers": 2,
        "numBytes": 3898294
      },
      "custom": {
        "someValue": 123,
        "someOtherValue": {
          "foo": "bar"
        },
        "someList": [1, 2, 3]
      }
    }

Use cases for this might be to simply store data in some backend in various events (for instance ``image.get`` or ``metadata.get``) and then fetch these and display then when requesting the stats endpoint (``stats.get``).

.. note:: The stats resource is not cache-able.

.. _status-resource:

Status resource - ``/status``
+++++++++++++++++++++++++++++

Imbo includes a simple status resource that can be used with for instance monitoring software.

.. code-block:: bash

    curl http://imbo/status.json

results in:

.. code-block:: javascript

    {
      "timestamp": "Tue, 24 Apr 2012 14:12:58 GMT",
      "database": true,
      "storage": true
    }

where ``timestamp`` is the current timestamp on the server, and ``database`` and ``storage`` are boolean values informing of the status of the current database and storage adapters respectively. If both are ``true`` the HTTP status code is ``200 OK``, and if one or both are ``false`` the status code is ``503``. When the status code is ``503`` the reason phrase will inform you whether it's the database or the storage adapter (or both) that is having issues. As soon as the status code does not equal ``200`` Imbo will no longer work as expected.

The reason for adapter failures depends on what kind of adapter you are using. The :ref:`file system storage adapter <filesystem-storage-adapter>` will for instance return a failure if it can no longer write to the storage directory. The :ref:`MongoDB <mongodb-database-adapter>` and :ref:`Doctrine <doctrine-database-adapter>` database adapters will fail if they can no longer connect to the server they are configured to communicate with.

**Typical response codes:**

* 200 OK
* 503 Database error
* 503 Storage error
* 503 Storage and database error

.. note:: The status resource is not cache-able.

.. _global-shorturl-resource:

Global short URL resource - ``/s/<id>``
+++++++++++++++++++++++++++++++++++++++

Images in Imbo can have short URLs associated with them, which are generated on demand when interacting with the :ref:`short URLs resource <shorturls-resource>`. These URLs can be used in place of the rather long original URLs which includes both access tokens and transformations.

The format of the random ID part of the short URL can be matched with the following `regular expression <http://en.wikipedia.org/wiki/Regular_expression>`_::

    /^[a-zA-Z0-9]{7}$/

There are some caveats regarding the short URLs:

1) If the data used to generate the short URL contained an image extension, content negotiation will not be applied to the short URL. You will always get the mime type associated with the extension used to generate the short URL.
2) If the data used to generate the short URL did not contain an image extension you can use the ``Accept`` header to decide the mime type of the generated image when requesting the short URL.
3) Short URLs do not support extensions, so you can not append ``.jpg`` to force ``image/jpeg``. If you need to make sure the image is always a JPEG, simply add ``jpg`` as an extension when generating the short URL.

You can read more about how to generate these URLs in the :ref:`short URLs section <shorturls-resource>`.

.. note:: In Imbo only images have short URLs

.. _user-resource:

User resource - ``/users/<user>``
+++++++++++++++++++++++++++++++++

The user resource represents a single user on the current Imbo installation. The output contains basic user information:

.. code-block:: bash

    curl http://imbo/users/<user>.json

results in:

.. code-block:: javascript

    {
      "user": "<user>",
      "numImages": 42,
      "lastModified": "Wed, 18 Apr 2012 15:12:52 GMT"
    }

where ``user`` is the user (the same used in the URI of the request), ``numImages`` is the number of images the user has stored in Imbo and ``lastModified`` is when the user last uploaded or deleted an image, or when the user last updated metadata of an image. If the user has not added any images yet, the ``lastModified`` value will be set to the current time on the server.

**Typical response codes:**

* 200 OK
* 304 Not modified
* 404 User not found

.. _images-resource:

Images resource - ``/users/<user>/images``
++++++++++++++++++++++++++++++++++++++++++

The images resource is the collection of images owned by a specific user. This resource can be used to search added images, and is also used to add new images to a collection.

Add an image
~~~~~~~~~~~~

To be able to display images stored in Imbo you will first need to add one or more images. This is done by requesting this endpoint with an image attached to the request body, and changing the HTTP METHOD to ``POST``. The body of the response for such a request contains a JSON object containing the image identifier of the added image:

.. code-block:: bash

    curl -XPOST http://imbo/users/<user>/images --data-binary @<file to add>

results in:

.. code-block:: javascript

    {
      "imageIdentifier": "<imageIdentifier>",
      "width": <width>,
      "height": <height>,
      "extension": "<extension>"
    }

The ``<imageIdentifier>`` in the response is the identifier of the added image. This is used with the `image resource<>`. The response body also contains the ``width``, ``height`` and ``extension`` of the image that was just added.

**Typical response codes:**

* 200 OK
* 201 Created
* 400 Bad request

Get image collections
~~~~~~~~~~~~~~~~~~~~~

The images resource can also be used to gather information on which images a user owns. This is done by requesting this resource using ``HTTP GET``. Supported query parameters are:

``page``
    The page number. Defaults to ``1``.

``limit``
    Number of images per page. Defaults to ``20``.

``metadata``
    Whether or not to include metadata in the output. Defaults to ``0``, set to ``1`` to enable.

``from``
    Fetch images starting from this `Unix timestamp <http://en.wikipedia.org/wiki/Unix_timestamp>`_.

``to``
    Fetch images up until this timestamp.

``fields[]``
    An array with fields to display. When not specified all fields will be displayed.

``sort[]``
    An array with fields to sort by. The direction of the sort is specified by appending ``asc`` or ``desc`` to the field, delimited by ``:``. If no direction is specified ``asc`` will be used. Example: ``?sort[]=size&sort[]=width:desc`` is the same as ``?sort[]=size:asc&sort[]=width:desc``. If no ``sort`` is specified Imbo will sort by the date the images was added, in a descending fashion.

``ids[]``
    An array of image identifiers to filter the results by.

``checksums[]``
    An array of image checksums to filter the results by.

``originalChecksums[]``
    An array of the original image checksums to filter the results by.

.. code-block:: bash

    curl "http://imbo/users/<user>/images.json?limit=1&metadata=1"

results in:

.. code-block:: javascript

    {
      "search": {
        "hits": 3,
        "page": 1,
        "limit": 1,
        "count": 1
      },
      "images": [
        {
          "added": "Mon, 10 Dec 2012 11:57:51 GMT",
          "updated": "Mon, 10 Dec 2012 11:57:51 GMT",
          "checksum": "<checksum>",
          "originalChecksum": "<originalChecksum>",
          "extension": "png",
          "size": 6791,
          "width": 1306,
          "height": 77,
          "mime": "image/png",
          "imageIdentifier": "<image>",
          "user": "<user>",
          "metadata": {
            "key": "value",
            "foo": "bar"
          }
        }
      ]
    }

The ``search`` object is data related to pagination, where ``hits`` is the number of images found by the query, ``page`` is the current page, ``limit`` is the current limit, and ``count`` is the number of images in the visible collection.

The ``images`` list contains image objects. Each object has the following fields:

* ``added``: A formatted date of when the image was added to Imbo.
* ``updated``: The formatted date of when the image was last updated (read: when metadata attached to the image was last updated, as the image itself never changes).
* ``checksum``: The MD5 checksum of the image blob stored in Imbo.
* ``originalChecksum``: The MD5 checksum of the original image. Might differ from ``<checksum>`` if event listeners that might change incoming images have been enabled. This field was added to Imbo in version ``1.2.0``. If this field is ``null`` when you query the images resource, you will need to manually update the database. If you have event listeners changing incoming images you might not want to simply set the original checksum to ``<checksum>`` as that might not be true.
* ``extension``: The original image extension.
* ``size``: The size of the image in bytes.
* ``width``: The width of the image in pixels.
* ``height``: The height of the image in pixels.
* ``mime``: The mime type of the image.
* ``imageIdentifier``: The image identifier stored in Imbo.
* ``user``: The user who owns the image.
* ``metadata``: A JSON object containing metadata attached to the image. This field is only available if the ``metadata`` query parameter described above is used.

**Typical response codes:**

* 200 OK
* 304 Not modified
* 404 User not found

.. _image-resource:

Image resource - ``/users/<user>/images/<image>``
+++++++++++++++++++++++++++++++++++++++++++++++++

The image resource represents specific images owned by a user. This resource is used to retrieve and remove images. It's also responsible for transforming the images based on the transformation parameters in the query.

Fetch images
~~~~~~~~~~~~

Fetching images added to Imbo is done by requesting the image identifiers of the images.

.. code-block:: bash

    curl http://imbo/users/<user>/images/<image>

results in:

.. code-block:: bash

    <binary data of the original image>

When fetching images Imbo also sends a set of custom HTTP response headers related to the image::

    X-Imbo-Originalextension: png
    X-Imbo-Originalmimetype: image/png
    X-Imbo-Originalfilesize: 45826
    X-Imbo-Originalheight: 390
    X-Imbo-Originalwidth: 380

These are all related to the image that was just requested.

How to use this resource to generate image transformations is described in the :doc:`../usage/image-transformations` chapter.

**Typical response codes:**

* 200 OK
* 304 Not modified
* 400 Bad request
* 404 Image not found

Delete images
~~~~~~~~~~~~~

Deleting images from Imbo is accomplished by requesting the image URIs using ``HTTP DELETE``. All metadata attached to the image will be removed as well.

.. code-block:: bash

    curl -XDELETE http://imbo/users/<user>/images/<image>

results in:

.. code-block:: javascript

    {
      "imageIdentifier": "<image>"
    }

where ``<image>`` is the image identifier of the image that was just deleted (the same as the one used in the URI).

**Typical response codes:**

* 200 OK
* 400 Bad request
* 404 Image not found

.. _shorturls-resource:

Short URLs resource - ``/users/<user>/images/<image>/shorturls``
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

This resource is used to create short URLs for images on demand, as well as removing all short URLs associated with a single image.

Create a short URL
~~~~~~~~~~~~~~~~~~

Creating a short URL is done by requesting this resource using ``HTTP POST`` while including some parameters for the short URL in the request body. The parameters must be specified as a JSON object, and the object supports the following fields:

* ``imageIdentfier``: The same image identifier as the one in the requested URI.
* ``user``: The same user as the one in the requested URI.
* ``extension``: An optional extension to the image, for instance ``jpg`` or ``png``.
* ``query``: The query string with transformations that will be applied. The format is the same as when requesting the image resource with one or more transformations. See the :doc:`image-transformations` chapter for more information regarding the transformation of images.

The generated ID of the short URL can be found in the response:

.. code-block:: bash

    curl -XPOST http://imbo/users/<user>/images/<image>/shorturls.json -d '{
      "imageIdentifier": "<image>",
      "user": "<user>",
      "extension": "jpg",
      "query": "t[]=thumbnail:width=75,height=75&t[]=desaturate"
    }'

results in:

.. code-block:: javascript

    {
      "id": "<id>"
    }

where ``<id>`` can be used with the :ref:`global short URL resource <global-shorturl-resource>` for requesting the image with the configured extension / transformations applied.

Delete all short URLs associated with an image
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you want to remove all short URLs associated with an image, you can request this resource using ``HTTP DELETE``:

.. code-block:: bash

    curl -XDELETE http://imbo/users/<user>/images/<image>/shorturls.json

results in:

.. code-block:: javascript

    {
      "imageIdentifier": "<image>"
    }

.. _shorturl-resource:

Short URL resource - ``/users/<user>/images/<image>/shorturls/<id>``
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

This resource can be used to remove a single short URL for a specific image variation.

This is achieved by simply requesting the resource with ``HTTP DELETE``, specifying the ID of the short URL in the URI:

.. code-block:: bash

    curl -XDELETE http://imbo/users/<user>/images/<image>/shorturls/<id>

results in:

.. code-block:: javascript

    {
      "id": "<id>"
    }

.. _metadata-resource:

Metadata resource - ``/users/<user>/images/<image>/metadata``
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Imbo can also be used to attach metadata to the stored images. The metadata is based on a simple ``key => value`` model, for instance:

* ``category: Music``
* ``band: Koldbrann``
* ``genre: Black metal``
* ``country: Norway``

Values can be nested ``key => value`` pairs.

Adding/replacing metadata
~~~~~~~~~~~~~~~~~~~~~~~~~

To add (or replace all existing metadata) on an image a client should make a request against this resource using ``HTTP PUT`` with the metadata attached in the request body as a JSON object. The response body will contain the added metadata.

.. code-block:: bash

    curl -XPUT http://imbo/users/<user>/images/<image>/metadata.json -d '{
      "beer":"Dark Horizon First Edition",
      "brewery":"Nøgne Ø",
      "style":"Imperial Stout"
    }'

results in:

.. code-block:: javascript

    {
      "beer": "Dark Horizon First Edition",
      "brewery": "Nøgne Ø",
      "style": "Imperial Stout"
    }

.. note:: When using the :ref:`Doctrine database adapter <doctrine-database-adapter>`, metadata keys can not contain ``::``.

**Typical response codes:**

* 200 OK
* 400 Bad request
* 400 Invalid metadata (when using the :ref:`Doctrine <doctrine-database-adapter>` adapter, and keys contain ``::``)
* 404 Image not found

Partially updating metadata
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Partial updates to metadata attached to an image is done by making a request with ``HTTP POST`` and attaching metadata to the request body as a JSON object. If the object contains keys that already exists in the metadata on the server the old values will be replaced by the ones found in the request body. New keys will be added to the metadata. The response will contain all metadata attached to the image after the update.

.. code-block:: bash

    curl -XPOST http://imbo/users/<user>/images/<image>/metadata.json -d '{
      "ABV":"16%",
      "score":"100/100"
    }'

results in:

.. code-block:: javascript

    {
      "beer": "Dark Horizon First Edition",
      "brewery": "Nøgne Ø",
      "style": "Imperial Stout",
      "ABV":"16%",
      "score":"100/100"
    }

if the image already included the first three keys as metadata.

.. note:: When using the :ref:`Doctrine database adapter <doctrine-database-adapter>`, metadata keys can not contain ``::``.

**Typical response codes:**

* 200 OK
* 400 Bad request
* 400 Invalid metadata (when using the :ref:`Doctrine <doctrine-database-adapter>` adapter, and keys contain ``::``)
* 404 Image not found

Fetch metadata
~~~~~~~~~~~~~~

Requests using ``HTTP GET`` on this resource returns all metadata attached to an image.

.. code-block:: bash

    curl http://imbo/users/<user>/images/<image>/metadata.json

results in:

.. code-block:: javascript

    {}

when there is no metadata stored, or for example

.. code-block:: javascript

    {
      "category": "Music",
      "band": "Koldbrann",
      "genre": "Black metal",
      "country": "Norway"
    }

if the image has metadata attached to it.

**Typical response codes:**

* 200 OK
* 304 Not modified
* 404 Image not found

Remove metadata
~~~~~~~~~~~~~~~

To remove metadata attached to an image a request using ``HTTP DELETE`` can be made.

.. code-block:: bash

    curl -XDELETE http://imbo/users/<user>/images/<image>/metadata.json

results in:

.. code-block:: javascript

    {}

**Typical response codes:**

* 200 OK
* 400 Bad request
* 404 Image not found

.. _global-images-resource:

Global images resource - ``/images``
++++++++++++++++++++++++++++++++++++

The global images resource is used to search for images across users, given that the public key has access to the images of these users.

This resource is read only, and behaves in the same way as described in the `Get image collections` section of :ref:`images-resource`. In addition to the parameters specified for `Get image collections`, the following query parameter must be specified:

``users[]``
    An array of users to get images for.

.. code-block:: bash

    curl "http://imbo/images?users[]=foo&users[]=bar"

results in a response with the exact same format as shown under `Get image collections`.

.. _publickey-resource:

Public key resource - ``/keys/<publicKey>``
+++++++++++++++++++++++++++++++++++++++++++

The public key resource provides a way for clients to dynamically add, remove and update public keys to be used as part of the access control routines. Not all access control adapters implement this functionality - in this case the configuration is done through configuration files.

A private key does not have any specific requirements, while a public key must match the following `regular expression <http://en.wikipedia.org/wiki/Regular_expression>`_::

    /^[a-zA-Z0-9_-]{1,}$/

Add a public key
~~~~~~~~~~~~~~~~

Every public key must also have a private key, which is used to sign and generate access tokens for requests. This is the only required parameter in the request body.

.. code-block:: bash

    curl -XPUT http://imbo/keys/<publicKey>.json -d '{"privateKey":"<privateKey>"}'

Check if a public key exist
~~~~~~~~~~~~~~~~~~~~~~~~~~~

A ``HEAD`` request can be used if you want to check if a public key exist. The public key used to sign the request must have access to the ``keys.head`` resource.

.. code-block:: bash

    curl -XHEAD http://imbo/keys/<publicKey>

**Typical response codes:**

* 200 OK
* 404 Public key not found

Change private key for a public key
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Use the same method as when adding a public key to change the private key.

Remove a public key
~~~~~~~~~~~~~~~~~~~

Public keys can be removed using a ``DELETE`` request. The public key used to sign the request must have access to the ``keys.delete`` resource.

.. code-block:: bash

    curl -XDELETE http://imbo/keys/<publicKey>.json

**Typical response codes:**

* 200 OK
* 201 Created
* 400 Bad request
* 404 Public key not found
* 405 Access control adapter is immutable

.. note:: The keys resource is not cache-able.

.. _groups-resource:

Groups resource - ``/groups``
+++++++++++++++++++++++++++++++++++++

The groups resource can list available resource groups, used in the access control routines.

List resource groups
~~~~~~~~~~~~~~~~~~~~

Requests using HTTP GET on this resource returns all available resource groups. Supported query parameters are:

``page``
    The page number. Defaults to ``1``.

``limit``
    Number of groups per page. Defaults to ``20``.

.. code-block:: bash

    curl http://imbo/groups.json

results in:

.. code-block:: javascript

    {"search":{"hits":0,"page":1,"limit":20,"count":0},"groups":[]}

when there are no resource groups defined, or for example

.. code-block:: javascript

    {
      "search": {
        "hits": 1,
        "page": 1,
        "limit": 20,
        "count": 1
      },
      "groups": [
        {
          "name": "read-stats",
          "resources": [
            "user.get",
            "user.head",
            "user.options"
          ]
        }
      ]
    }

if there are resource groups defined.

**Typical response codes:**

* 200 OK

.. _group-resource:

Group resource - ``/groups/<groupName>``
++++++++++++++++++++++++++++++++++++++++

The group resource enables adding, modifying and deleting resource groups used in the access control routine. Not all access control adapters allow modification of groups - in this case the configuration is done through configuration files, and PUT/DELETE operations will result in an HTTP 405 response.

List resources of a group
~~~~~~~~~~~~~~~~~~~~~~~~~

Requests using HTTP GET on this resource returns all the resources the group consists of.

.. code-block:: bash

    curl http://imbo/groups/<group>.json

results in:

.. code-block:: javascript

    {
      "resources": [
        "user.get",
        "user.head",
        "user.options"
      ]
    }

Add/modify resources for a group
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Requests using HTTP PUT on this resource either adds a new group with the given name, or if it already exists, updates it. The request body should contain an array of resources the group should consist of.

.. code-block:: bash

    curl -XPUT http://imbo/groups/<group>.json -d '[
      "user.get",
      "stats.get"
    ]'

Delete a resource group
~~~~~~~~~~~~~~~~~~~~~~~

Requests using HTTP DELETE on this resource will remove the entire resource group. Note that any access control rules that are using this resource group will also be deleted, since they are now invalid.

.. code-block:: bash

    curl -XDELETE http://imbo/groups/<group>.json


**Typical response codes:**

* 200 OK
* 201 Created
* 404 Group not found
* 405 Access control adapter is immutable

.. _access-rules-resource:

Access rules resource - ``/keys/<publicKey>/access``
++++++++++++++++++++++++++++++++++++++++++++++++++++

The access rules endpoint allows you to add rules that give a public key access to a specified set of resources. These rules can also be defined on a per-user basis. Instead of defining a list of resources, you also have the option to specify a :ref:`resource group <groups-resource>`.

Listing access control rules
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Requests using HTTP GET on this resource returns all the access control rules defined for the given public key.

.. code-block:: bash

    curl http://imbo/keys/<publicKey>/access.json

results in:

.. code-block:: javascript

    [
      {
        "id": 1,
        "resources": ['images.get', 'image.get', 'images.post', 'image.delete'],
        "users": [
          "user1",
          "user2"
        ]
      },
      {
        "id": 2,
        "group": "read-stats",
        "users": [
          "user1",
          "user2"
        ]
      }
    ]

Adding access control rules
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Requests using HTTP POST on this resource adds new rules to the given public key. The request body should contain an array of rules. The parameters for a rule must be specified as JSON objects, where the object supports the following fields:

* ``users``: Defines on which users the public key should have access to the defined resources. Either an array of users or the string ``*`` (all users).
* ``resources``: An array of resources you want the public key to have access to.
* ``group``: A resource group the public key should have access to.

.. note:: A rule must contain *either* ``resources`` or ``group``, not both. ``users`` is required.

.. code-block:: bash

    curl -XPOST http://imbo/keys/<publicKey>/access -d '[{
      "resources": ["user.get", "image.get", "images.get", "metadata.get"],
      "users": "*"
    }]'

**Typical response codes:**

* 200 OK
* 400 No access rule data provided
* 404 Public key not found
* 405 Access control adapter is immutable

.. _access-rule-resource:

Access rule resource - ``/keys/<publicKey>/access/<ruleId>``
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

The access rule endpoint allows you to see which resources and users a given access control rule contains. It also allows you to remove a specific access control rule.

Get access rule details
~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: bash

    curl http://imbo/keys/<publicKey>/access/<ruleId>.json

results in:

.. code-block:: javascript

    {
      "id": 1,
      "resources": ['images.get', 'image.get', 'images.post', 'image.delete'],
      "users": [
        "user1",
        "user2"
      ]
    }

Removing an access rule
~~~~~~~~~~~~~~~~~~~~~~~

Requests using HTTP DELETE on this resource removes the access control rule, given the access control adapter supports mutations.

.. code-block:: bash

    curl -XDELETE http://imbo/keys/<publicKey>/access/<ruleId>

**Typical response codes:**

* 200 OK
* 404 Public key not found
* 404 Access rule not found
* 405 Access control adapter is immutable

.. _access-tokens:

Access tokens
-------------

Access tokens are enforced by an event listener that is enabled in the default configuration file. The access tokens are used to prevent `DoS <http://en.wikipedia.org/wiki/Denial-of-service_attack>`_ attacks so think twice before you disable the event listener.

An access token, when enforced by the event listener, must be supplied in the URI using the ``accessToken`` query parameter and without it, most ``GET`` and ``HEAD`` requests will result in a ``400 Bad request`` response. The value of the ``accessToken`` parameter is a `Hash-based Message Authentication Code <http://en.wikipedia.org/wiki/HMAC>`_ (HMAC). The code is a `SHA-256 <http://en.wikipedia.org/wiki/SHA-2>`_ hash of the URI itself using the private key of the user as the secret key. It is very important that the URI is **not** URL-encoded when generating the hash. Below is an example on how to generate a valid access token for a specific image using PHP:

.. literalinclude:: ../examples/generateAccessToken.php
    :language: php
    :linenos:

and Python:

.. literalinclude:: ../examples/generateAccessToken.py
    :language: python
    :linenos:

and Ruby:

.. literalinclude:: ../examples/generateAccessToken.rb
    :language: ruby
    :linenos:

If the event listener enforcing the access token check is removed, Imbo will ignore the ``accessToken`` query parameter completely. If you wish to implement your own form of access token you can do this by implementing an event listener of your own (see :ref:`custom-event-listeners` for more information).

Prior to Imbo-1.0.0 there was no rule that the URL had to be completely URL-decoded prior to generating the access token in the clients. Because of this Imbo will also try to re-generate the access token server side by using the URL as-is. This feature has been added to ease the transition to Imbo >= 1.0.0, and will be removed some time in the future.

.. _signing-write-requests:

Signing write requests
----------------------

To be able to write to Imbo the user agent will have to specify two request headers: ``X-Imbo-Authenticate-Signature`` and ``X-Imbo-Authenticate-Timestamp``.

``X-Imbo-Authenticate-Signature`` is, like the access token, an HMAC (also using SHA-256 and the private key of the user).

The data for the hash is generated using the following elements:

* HTTP method (``PUT``, ``POST`` or ``DELETE``)
* The URI
* Public key
* GMT timestamp (``YYYY-MM-DDTHH:MM:SSZ``, for instance: ``2011-02-01T14:33:03Z``)

These elements are concatenated in the above order with ``|`` as a delimiter character, and a hash is generated using the private key of the user. The following snippet shows how this can be accomplished in PHP when deleting an image:

.. literalinclude:: ../examples/generateSignatureForDelete.php
    :language: php
    :linenos:

and Python (using the `Requests <http://docs.python-requests.org>`_ library):

.. literalinclude:: ../examples/generateSignatureForDelete.py
    :language: python
    :linenos:

and Ruby (using the `httpclient <https://rubygems.org/gems/httpclient>`_ gem):

.. literalinclude:: ../examples/generateSignatureForDelete.rb
    :language: ruby
    :linenos:

Imbo requires that ``X-Imbo-Authenticate-Timestamp`` is within ± 120 seconds of the current time on the server.

As with the access token the signature check is enforced by an event listener that can also be disabled. If you disable this event listener you effectively open up for writing from anybody, which you probably don't want to do.

If you want to implement your own authentication paradigm you can do this by creating a custom event listener.

Supported content types
-----------------------

Imbo currently responds with images (jpg, gif and png), `JSON <http://en.wikipedia.org/wiki/JSON>`_ and `XML <http://en.wikipedia.org/wiki/XML>`_, but only accepts images (jpg, gif and png) and JSON as input.

Imbo will do content negotiation using the `Accept <http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html>`_ header found in the request, unless you specify a file extension, in which case Imbo will deliver the type requested without looking at the ``Accept`` header.

The default content type for non-image responses is JSON. Examples in this chapter uses the ``.json`` extension. Change it to ``.xml`` to get the XML representation instead. You can also skip the extension and force a specific content type using the ``Accept`` header:

.. code-block:: bash

    curl http://imbo/status.json

and

.. code-block:: bash

    curl -H "Accept: application/json" http://imbo/status

will end up with the same content type. Use ``application/xml`` for XML.

If you use JSON you can wrap the content in a function (`JSONP <http://en.wikipedia.org/wiki/JSONP>`_) by using one of the following query parameters:

* ``callback``
* ``jsonp``
* ``json``

.. code-block:: bash

    curl http://imbo/status.json?callback=func

will result in:

.. code-block:: javascript

    func(
      {
        "date": "Mon, 05 Nov 2012 19:18:40 GMT",
        "database": true,
        "storage": true
      }
    )

For images the default mime-type is the original mime-type of the image. If you add an ``image/gif`` image and fetch that image with ``Accept: */*`` or ``Accept: image/*`` the mime-type of the image returned will be ``image/gif``. To choose a different mime type either change the ``Accept`` header, or use ``.jpg`` or ``.png`` (for ``image/jpeg`` and ``image/png`` respectively).

An exception to this is if the configuration option  :ref:`contentNegotiateImages <configuration-content-negotiation>` is set to ``false``, in which case Imbo will not convert the image to a different format than the original, unless explicitly told to do so by specifying an extension (``.jpg``, ``.png``, ``.gif`` etc).

Cache headers
-------------

Most responses from Imbo includes a set of cache-related headers that enables shared caches and user agents to cache content.

Cache-Control
+++++++++++++

Some responses from Imbo are not cache-able. These will typically include ``Cache-Control: max-age=0, no-store, private``. The following resources are not cache-able:

* :ref:`index-resource`
* :ref:`stats-resource`
* :ref:`status-resource`

All other resources will include ``Cache-Control: public``. The :ref:`image <image-resource>` and :ref:`short url <global-shorturl-resource>` resources will also set a ``max-age``, resulting in the following header: ``Cache-Control: max-age=31536000, public``.

ETag
++++

Imbo provides `entity tags <http://en.wikipedia.org/wiki/HTTP_ETag>`_ for cache validation mechanisms. User agents can use the ``ETag`` response header to do conditional requests further down the road (by specifying the original ``ETag`` value in the ``If-None-Match`` request header). This results in saved bandwidth as web caches and Imbo servers no longer need to send the response body, as the one cached by the user agent can be re-used. This is achieved by sending ``304 Not Modified`` back to the user agent, instead of ``200 OK``.

The following resources in Imbo will include an ETag:

* :ref:`user-resource`
* :ref:`images-resource`
* :ref:`image-resource`
* :ref:`metadata-resource`
* :ref:`global-shorturl-resource`

The value of the ``ETag`` header is simply the MD5 sum of the content in the response body, enclosed in quotes. For instance ``ETag: "fd2fd87a2f5288be31c289e70e916123"``.

Last-Modified
+++++++++++++

Imbo also includes a ``Last-Modified`` response header for resources that has a know last modification date, and these resources are:

* :ref:`user-resource`: The date of when the user last added or deleted an image, or manipulated the metadata of an image. If the user don't have any images yet, the value of this date will be the current timestamp.
* :ref:`images-resource`: The date of when the user last modified an image in the collection (either the image itself, or metadata attached to the image).
* :ref:`image-resource`: The date of when the image was added (or replaced), or when the metadata of the image was last modified.
* :ref:`metadata-resource`: The date of when the metadata of the image was last modified.
* :ref:`global-shorturl-resource`: Same as the date of the original image.

User agents can use the value of the ``Last-Modified`` header in the ``If-Modified-Since`` request header to make a conditional request. The value of the ``Last-Modified`` header is an `HTTP-date <http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.3.1>`_, for instance ``Last-Modified: Wed, 12 Feb 2014 09:46:02 GMT``.

Errors
------

When an error occurs Imbo will respond with a fitting HTTP response code along with a JSON object explaining what went wrong.

.. code-block:: bash

    curl -g "http://imbo/users/<user>/foobar"

results in:

.. code-block:: javascript

    {
      "error": {
        "imboErrorCode": 0,
        "date": "Wed, 12 Dec 2012 21:15:01 GMT",
        "message": "Not Found",
        "code": 404
      }
    }

The ``code`` is the HTTP response code, ``message`` is a human readable error message, ``date`` is when the error occurred on the server, and ``imboErrorCode`` is an internal error code that can be used by the user agent to distinguish between similar errors (such as ``400 Bad request``).

The JSON object will also include ``imageIdentifier`` if the request was made against the image or the metadata resource.
