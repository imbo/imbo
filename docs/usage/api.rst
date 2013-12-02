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

The index resource shows the version of the Imbo installation along with some external URL's for Imbo-related information, and some internal URL's for the available endpoints.

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
        "user": "http://imbo/users/{publicKey}",
        "images": "http://imbo/users/{publicKey}/images",
        "image": "http://imbo/users/{publicKey}/images/{imageIdentifier}",
        "shortImageUrl": "http://imbo/s/{id}",
        "metadata": "http://imbo/users/{publicKey}/images/{imageIdentifier}/metadata"
      }
    }

This resource does not support any extensions in the URI, so you will need to use the ``Accept`` header to fetch different representations of the data.

The index resource does not require any authentication per default.

**Typical response codes:**

* 200 Hell Yeah

.. _stats-resource:

Stats resource - ``/stats``
+++++++++++++++++++++++++++

Imbo provides an endpoint for fetching simple statistics about the data stored in Imbo.

.. code-block:: bash

    curl http://imbo/stats.json

results in:

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
    return array(
        // ...

        'eventListeners' => array(
            'customStats' => array(
                'events' => array('stats.get'),
                'callback' => function($event) {
                    // Fetch the model from the response
                    $model = $event->getResponse()->getModel();

                    // Set some values
                    $model['someValue'] = 123;
                    $model['someOtherValue'] = array(
                        'foo' => 'bar',
                    );
                    $model['someList'] = array(1, 2, 3);
                }
            ),
        ),

        // ...
    );

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

The reason for adapter failures depends on what kind of adapter you are using. The :ref:`file system storage adapter <filesystem-storage-adapter>` will for instance return a failure if it can no longer write to the storage directory. The :ref:`MongoDB <mongodb-database-adapter>` and :ref:`Doctrine <doctrine-database-adapter>` database adapters will fail if they can no longer connect to the server they are configured to talk to.

**Typical response codes:**

* 200 OK
* 503 Database error
* 503 Storage error
* 503 Storage and database error

.. note:: The status resource is not cache-able.

.. _user-resource:

User resource - ``/users/<user>``
+++++++++++++++++++++++++++++++++

The user resource represents a single user on the current Imbo installation. The output contains basic user information:

.. code-block:: bash

    curl http://imbo/users/<user>.json

results in:

.. code-block:: javascript

    {
      "publicKey": "<user>",
      "numImages": 42,
      "lastModified": "Wed, 18 Apr 2012 15:12:52 GMT"
    }

where ``publicKey`` is the public key of the user (the same used in the URI of the request), ``numImages`` is the number of images the user has stored in Imbo and ``lastModified`` is when the user last uploaded or deleted an image, or when the user last updated metadata of an image. If the user has not added any images yet, the ``lastModified`` value will be set to the current time on the server.

**Typical response codes:**

* 200 OK
* 304 Not modified
* 404 Public key not found

.. _images-resource:

Images resource - ``/users/<user>/images``
++++++++++++++++++++++++++++++++++++++++++

The images resource represents a collection of images owned by a specific user. Supported query parameters are:

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

``fields``
    A comma separated list of fields to display. When not specified all fields will be displayed.

``sort``
    A comma separated list of fields to sort by. The direction of the sort is specified by appending ``asc`` or ``desc`` to the field, delimited by ``:``. If no direction is specified ``asc`` will be used. Example: ``?sort=size,width:desc`` is the same as ``?sort=size:asc,width:desc``. If no ``sort`` is specified Imbo will sort by the date the images was added, in a descending fashion.

.. code-block:: bash

    curl "http://imbo/users/<user>/images.json?limit=1&metadata=1"

results in:

.. code-block:: javascript

    [
      {
        "added": "Mon, 10 Dec 2012 11:57:51 GMT",
        "extension": "png",
        "height": 77,
        "imageIdentifier": "<image>",
        "metadata": {
          "key": "value",
          "foo": "bar"
        },
        "mime": "image/png",
        "publicKey": "<user>",
        "size": 6791,
        "updated": "Mon, 10 Dec 2012 11:57:51 GMT",
        "width": 1306
      }
    ]

where ``added`` is a formatted date of when the image was added to Imbo, ``extension`` is the original image extension, ``height`` is the height of the image in pixels, ``imageIdentifier`` is the image identifier (`MD5 checksum <http://en.wikipedia.org/wiki/MD5>`_ of the file itself), ``metadata`` is a JSON object containing metadata attached to the image, ``mime`` is the mime type of the image, ``publicKey`` is the public key of the user who owns the image, ``size`` is the size of the image in bytes, ``updated`` is a formatted date of when the image was last updated (read: when metadata attached to the image was last updated, as the image itself never changes), and ``width`` is the width of the image in pixels.

The ``metadata`` field is only available if you used the ``metadata`` query parameter described above.

**Typical response codes:**

* 200 OK
* 304 Not modified
* 404 Public key not found

.. _image-resource:

Image resource - ``/users/<user>/images/<image>``
+++++++++++++++++++++++++++++++++++++++++++++++++

The image resource represents specific images owned by a user. This resource is used to add, retrieve and remove images. It's also responsible for transforming the images based on the transformation parameters in the query.

Add an image
~~~~~~~~~~~~

To be able to display images stored in Imbo you will first need to add one or more images. This is done by requesting this endpoint with an image attached to the request body, and changing the HTTP METHOD to ``PUT``. The body of the response for such a request contains a JSON object containing the image identifier of the added image:

.. code-block:: bash

    curl -XPUT http://imbo/users/<user>/images/<image> --data-binary @<file to add>

results in:

.. code-block:: javascript

    {
      "imageIdentifier": "<imageIdentifier>",
      "width": <width>,
      "height": <height>,
      "extension": "<extension>"
    }

The ``<image>`` part of the URI is the `MD5 checksum <http://en.wikipedia.org/wiki/MD5>`_ of the file itself. The ``<imageIdentifier>`` in the response can be used to fetch the added image and apply transformations to it. The output from this method is important as the ``<imageIdentifier>`` in the response might not be the same as the one used in the URI when adding the image (which might occur if for instance event listeners transform the image in some way before Imbo stores it, like the :ref:`auto-rotate-image-event-listener` and :ref:`max-image-size-event-listener` event listeners). The response body also contains the width, height and extension of the image that was just added.

**Typical response codes:**

* 200 OK
* 201 Created
* 400 Bad Request

Fetch images
~~~~~~~~~~~~

Fetching images added to Imbo is done by requesting the image identifiers (checksum) of the images.

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
    X-Imbo-ShortUrl: http://imbo/s/w7CiqDM

These are all related to the image that was just requested.

How to use this resource to generate image transformations is described in the :doc:`../usage/image-transformations` chapter.

**Typical response codes:**

* 200 OK
* 304 Not modified
* 400 Bad Request
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
* 400 Bad Request
* 404 Image not found

.. _shorturl-resource:

ShortURL resource - ``/s/<id>``
+++++++++++++++++++++++++++++++

Images in Imbo have short URLs associated with them, which are generated on request when you access an image (with or without image transformations) for the first time. These URLs do not take any query parameters and can be used in place for original image URLs. To fetch these URLs you can request an image using HTTP HEAD, then look for the `X-Imbo-ShortUrl` header in the response::

    curl -Ig "http://imbo/users/<user>/images/<image>?t[]=thumbnail&t[]=desaturate&t[]=border&accessToken=f3fa1d9f0649cfad61e840a6e09b156e851858799364d1d8ee61b386e10b0c05"|grep Imbo

results in (some headers omitted):

.. code-block:: none
    :emphasize-lines: 6

    X-Imbo-OriginalMimeType: image/gif
    X-Imbo-OriginalWidth: 771
    X-Imbo-OriginalHeight: 771
    X-Imbo-OriginalFileSize: 152066
    X-Imbo-OriginalExtension: gif
    X-Imbo-ShortUrl: http://imbo/s/3VEFrpB
    X-Imbo-ImageIdentifier: 4492acb937a1f056ae43509bc7f85d21

The value of the ``X-Imbo-ShortUrl`` can be used to request the image with the applied transformations, and does not require an access token query parameter.

The format of the random ID part of the short URL can be matched with the following `regular expression <http://en.wikipedia.org/wiki/Regular_expression>`_::

    |^[a-zA-Z0-9]{7}$|

There are some caveats regarding the short URLs:

1) If the URL used to generate the short URL contained an image extension, content negotiation will not be applied to the short URL. You will always get the mime type associated with the extension used to generate the short URL.
2) If the URL used to generate the short URL did not contain an image extension you can use the ``Accept`` header to decide the mime type of the generated image when requesting the short URL.
3) Short URLs do not support extensions, so you can not append ``.jpg`` to force ``image/jpeg``. If you need to make sure the image is always a JPEG, simply append ``.jpg`` to the URL when generating the short URL.

.. note:: In Imbo only images have short URL's

.. _metadata-resource:

Metadata resource - ``/users/<user>/images/<image>/metadata``
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Imbo can also be used to attach metadata to the stored images. The metadata is based on a simple ``key => value`` model, for instance:

* ``category: Music``
* ``band: Koldbrann``
* ``genre: Black metal``
* ``country: Norway``

Adding/replacing metadata
~~~~~~~~~~~~~~~~~~~~~~~~~

To add (or replace all existing metadata) on an image a client should make a request against this resource using ``HTTP PUT`` with the metadata attached in the request body as a JSON object.

.. code-block:: bash

    curl -XPUT http://imbo/users/<user>/images/<image>/metadata.json -d '{
        "beer":"Dark Horizon First Edition",
        "brewery":"Nøgne Ø",
        "style":"Imperial Stout"
    }'

results in:

.. code-block:: javascript

    {
      "imageIdentifier": "<image>"
    }

where ``<image>`` is the image that just got updated.

.. note:: When using the :ref:`Doctrine database adapter <doctrine-database-adapter>`, metadata keys can not contain ``::``.

**Typical response codes:**

* 200 OK
* 400 Bad Request
* 400 Invalid metadata (when using the :ref:`Doctrine <doctrine-database-adapter>` adapter, and keys contain ``::``)
* 404 Image not found

Partially updating metadata
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Partial updates to metadata attached to an image is done by making a request with ``HTTP POST`` and attaching metadata to the request body as a JSON object. If the object contains keys that already exists in the metadata on the server the old values will be replaced by the ones found in the request body. New keys will be added to the metadata.

.. code-block:: bash

    curl -XPOST http://imbo/users/<user>/images/<image>/metadata.json -d '{
        "ABV":"16%",
        "score":"100/100"
    }'

results in:

.. code-block:: javascript

    {
      "imageIdentifier": "<image>"
    }

where ``<image>`` is the image that just got updated.

.. note:: When using the :ref:`Doctrine database adapter <doctrine-database-adapter>`, metadata keys can not contain ``::``.

**Typical response codes:**

* 200 OK
* 400 Bad Request
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

    {
      "imageIdentifier":"<image>"
    }

where ``<image>`` is the image identifier of the image that just got all its metadata deleted.

**Typical response codes:**

* 200 OK
* 400 Bad Request
* 404 Image not found

.. _access-tokens:

Access tokens
-------------

Access tokens are enforced by an event listener that is enabled in the default configuration file. The access tokens are used to prevent `DoS <http://en.wikipedia.org/wiki/Denial-of-service_attack>`_ attacks so think twice before you disable the event listener.

An access token, when enforced by the event listener, must be supplied in the URI using the ``accessToken`` query parameter and without it, most ``GET`` and ``HEAD`` requests will result in a ``400 Bad Request`` response. The value of the ``accessToken`` parameter is a `Hash-based Message Authentication Code <http://en.wikipedia.org/wiki/HMAC>`_ (HMAC). The code is a `SHA-256 <http://en.wikipedia.org/wiki/SHA-2>`_ hash of the URI itself using the private key of the user as the secret key. It is very important that the URI is not URL-encoded when generating the hash. Below is an example on how to generate a valid access token for a specific image using PHP:

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

.. _signing-write-requests:

Signing write requests
----------------------

To be able to write to Imbo the user agent will have to specify two request headers: ``X-Imbo-Authenticate-Signature`` and ``X-Imbo-Authenticate-Timestamp``.

``X-Imbo-Authenticate-Signature`` is, like the access token, an HMAC (also using SHA-256 and the private key of the user).

The data for the hash is generated using the following elements:

* HTTP method (``PUT``, ``POST`` or ``DELETE``)
* The URI
* Public key of the user
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

Errors
------

When an error occurs Imbo will respond with a fitting HTTP response code along with a JSON object explaining what went wrong.

.. code-block:: bash

    curl -g "http://imbo/users/<user>/images/<image>.jpg?t[]=foobar"

results in:

.. code-block:: javascript

    {
      "error": {
        "code": 400,
        "message": "Unknown transformation: foobar",
        "date": "Wed, 12 Dec 2012 21:15:01 GMT",
        "imboErrorCode": 0
      },
      "imageIdentifier": "<image>"
    }

The ``code`` is the HTTP response code, ``message`` is a human readable error message, ``date`` is when the error occurred on the server, and ``imboErrorCode`` is an internal error code that can be used by the user agent to distinguish between similar errors (such as ``400 Bad Request``).

The JSON object will also include ``imageIdentifier`` if the request was made against the image or the metadata resource.

If the user agent specifies a nonexistent username the following occurs:

.. code-block:: bash

    curl http://imbo/users/<non-existing-user>.json

results in:

.. code-block:: javascript

    {
      "error": {
        "code": 404,
        "message": "Public key not found",
        "date": "Mon, 13 Aug 2012 17:22:37 GMT",
        "imboErrorCode": 100
      }
    }
