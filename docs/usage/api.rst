RESTful API
===========

Imbo uses a `RESTful`_ API to manage the stored images and metadata. Each image is identified by a public key (the "username") and an MD5 checksum of the file itself. The public key and the image identifier will be referred to as ``<user>`` and ``<image>`` respectively for the remainder of this document. For all `cURL`_ examples ``imbo`` will be used as a host name. The examples will also omit access tokens and authentication signatures.

.. _cURL: http://curl.haxx.se/
.. _RESTful: http://en.wikipedia.org/wiki/REST

Content types
-------------

Currently Imbo responds with images (jpg, gif and png) and `JSON`_, `XML`_ and `HTML`_, but only accepts images (jpg, gif and png) and JSON as input.

Imbo will do content negotiation using the `Accept`_ header found in the request, unless you specify a file extension, in which case Imbo will deliver the type requested without looking at the Accept header.

The default `Content-Type`_ for non-image responses is JSON, and for most examples in this document you will see the ``.json`` extension being used. Change that to ``.html`` or ``.xml`` to get HTML and XML respectively. You can also skip the extension and force a specific Content-Type using the Accept header:

.. code-block:: bash

    $ curl http://imbo/status.json

and

.. code-block:: bash

    $ curl -H "Accept: application/json" http://imbo/status

will end up with the same content-type. Use ``application/xml`` for XML, and ``text/html`` for HTML.

If you use JSON you can wrap the content in a function (`JSONP`_) by using one of the following query parameters:

* ``callback``
* ``jsonp``
* ``json``

.. code-block:: bash

    $ curl http://imbo/status.json?callback=func

will result in:

.. code-block:: javascript

    func(
      {
        "date": "Mon, 05 Nov 2012 19:18:40 GMT",
        "database": true,
        "storage": true
      }
    )

.. _Accept: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
.. _Content-Type: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
.. _JSON: http://en.wikipedia.org/wiki/JSON
.. _JSONP: http://en.wikipedia.org/wiki/JSONP
.. _XML: http://en.wikipedia.org/wiki/XML
.. _HTML: http://en.wikipedia.org/wiki/HTML

Resources
---------

In this section you will find information on the different resources Imbo's RESTful API expose, along with their capabilities:

.. contents:: Available resources
    :local:
    :depth: 1

.. _status-resource:

Status resource
+++++++++++++++

Imbo includes a simple status resource that can be used with for instance monitoring software.

.. code-block:: bash

    $ curl http://imbo/status.json

results in:

.. code-block:: javascript

    {
      "timestamp": "Tue, 24 Apr 2012 14:12:58 GMT",
      "database": true,
      "storage": true
    }

where ``timestamp`` is the current timestamp on the server, and ``database`` and ``storage`` are boolean values informing of the status of the current database and storage drivers respectively. If both are ``true`` the HTTP status code is ``200 OK``, and if one or both are ``false`` the status code is ``500``. When the status code is ``500`` the status message will inform you whether it's the database or the storage driver (or both) that is having issues.

**Typical response codes:**

* 200 OK
* 500 Internal Server Error

.. _user-resource:

User resource
+++++++++++++

The user resource represents a single user on the current Imbo installation.

GET /users/<user>
~~~~~~~~~~~~~~~~~

Fetch information about a specific user. The output contains basic user information:

.. code-block:: bash

    $ curl http://imbo/users/<user>.json

results in:

.. code-block:: javascript

    {
      "publicKey": "<user>",
      "numImages": 42,
      "lastModified": "Wed, 18 Apr 2012 15:12:52 GMT"
    }

where ``publicKey`` is the public key of the user, ``numImages`` is the number of images the user has stored in Imbo and ``lastModified`` is when the user last uploaded an image or updated metadata of an image.

**Typical response codes:**

* 200 OK
* 304 Not modified
* 404 Not found

.. _images-resource:

Images resource
+++++++++++++++

The images resource represents a collection of images owned by a specific user.

GET /users/<user>/images
~~~~~~~~~~~~~~~~~~~~~~~~

Get information about the images stored in Imbo for a specific user. Supported query parameters are:

``page``
    The page number. Defaults to ``1``.

``limit``
    Number of images pr. page. Defaults to ``20``.

``metadata``
    Whether or not to include metadata in the output. Defaults to ``0``, set to ``1`` to enable.

``from``
    Fetch images starting from this Unix timestamp.

``to``
    Fetch images up until this timestamp.

.. code-block:: bash

    $ curl "http://imbo/users/<user>/images.json?limit=1&metadata=1"

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

where ``added`` is a formatted date of when the image was added to Imbo, ``extension`` is the original image extension, ``height`` is the height of the image in pixels, ``imageIdentifier`` is the image identifier (MD5 checksum of the file itself), ``metadata`` is a JSON object containing metadata attached to the image, ``mime`` is the mime type of the image, ``publicKey`` is the public key of the user who owns the image, ``size`` is the size of the image in bytes, ``updated`` is a formatted date of when the image was last updated (read: when metadata attached to the image was last updated, as the image itself never changes), and ``width`` is the width of the image in pixels.

The ``metadata`` field is only available if you used the ``metadata`` query parameter described above.

Images in the array are ordered on the ``added`` field in a descending fashion.

**Typical response codes:**

* 200 OK
* 304 Not modified
* 404 Not found

.. _image-resource:

Image resource
++++++++++++++

The image resource represents specific images owned by a user.

GET /users/<user>/images/<image>
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Fetch the image identified by ``<image>`` owned by ``<user>``. Without any query parameters this will return the original image.

.. code-block:: bash

    $ curl http://imbo/users/<user>/images/<image>

results in:

.. code-block:: bash

    <binary data of the original image>

**Typical response codes:**

* 200 OK
* 304 Not modified
* 400 Bad Request
* 404 Not found

Image transformations
~~~~~~~~~~~~~~~~~~~~~

Below you can find information on the transformations shipped with Imbo along with their parameters.

border
######

This transformation will apply a border around the image.

**Parameters:**

``color``
    Color of the border in hexadecimal. Defaults to ``000000`` (You can also specify short values like ``f00`` (``ff0000``)).

``width``
    Width of the border in pixels on the left and right sides of the image. Defaults to ``1``.

``height``
    Height of the border in pixels on the top and bottom sides of the image. Defaults to ``1``.

**Examples:**

* ``t[]=border``
* ``t[]=border:color=000``
* ``t[]=border:color=f00,width=2,height=2``

canvas
######

This transformation can be used to change the canvas of the original image.

**Parameters:**

``width``
    Width of the surrounding canvas in pixels. If omitted the width of ``<image>`` will be used.

``height``
    Height of the surrounding canvas in pixels. If omitted the height of ``<image>`` will be used.

``mode``
    The placement mode of the original image. ``free``, ``center``, ``center-x`` and ``center-y`` are available values. Defaults to ``free``.

``x``
    X coordinate of the placement of the upper left corner of the existing image. Only used for modes: ``free`` and ``center-y``.

``y``
    Y coordinate of the placement of the upper left corner of the existing image. Only used for modes: ``free`` and ``center-x``.

``bg``
    Background color of the canvas. Defaults to ``ffffff`` (also supports short values like ``f00`` (``ff0000``)).

**Examples:**

* ``t[]=canvas:width=200,mode=center``
* ``t[]=canvas:width=200,height=200,x=10,y=10,bg=000``
* ``t[]=canvas:width=200,height=200,x=10,mode=center-y``
* ``t[]=canvas:width=200,height=200,y=10,mode=center-x``

compress
########

This transformation compresses images on the fly resulting in a smaller payload.

**Parameters:**

``quality``
    Quality of the resulting image. 100 is maximum quality (lowest compression rate).

**Examples:**

* ``t[]=compress:quality=40``

.. warning::
    This transformation currently only works as expected for ``image/jpeg`` images.

.. _convert-transformation:

convert
#######

This transformation can be used to change the image type. It is not applied like the other transformations, but is triggered when specifying a custom extension to the ``<image>``. Currently Imbo can convert to:

* ``jpg``
* ``png``
* ``gif``

**Examples:**

* ``curl http://imbo/users/<user>/images/<image>.gif``
* ``curl http://imbo/users/<user>/images/<image>.jpg``
* ``curl http://imbo/users/<user>/images/<image>.png``

crop
####

This transformation is used to crop the image.

**Parameters:**

``x``
    The X coordinate of the cropped region's top left corner.

``y``
    The Y coordinate of the cropped region's top left corner.

``width``
    The width of the crop in pixels.

``height``
    The height of the crop in pixels.

**Examples:**

* ``t[]=crop:x=10,y=25,width=250,height=150``

desaturate
##########

This transformation desaturates the image (in practice, gray scales it).

**Examples:**

* ``t[]=desaturate``

flipHorizontally
################

This transformation flips the image horizontally.

**Examples:**

* ``t[]=flipHorizontally``

flipVertically
##############

This transformation flips the image vertically.

**Examples:**

* ``t[]=flipVertically``

maxSize
#######

This transformation will resize the image using the original aspect ratio. Two parameters are supported and at least one of them must be supplied to apply the transformation.

Note the difference from the :ref:`resize` transformation: given both ``width`` and ``height``, the resulting image will not be the same width and height as specified unless the aspect ratio is the same.

**Parameters:**

``width``
    The max width of the resulting image in pixels. If not specified the width will be calculated using the same aspect ratio as the original image.

``height``
    The max height of the resulting image in pixels. If not specified the height will be calculated using the same aspect ratio as the original image.

**Examples:**

* ``t[]=maxSize:width=100``
* ``t[]=maxSize:height=100``
* ``t[]=maxSize:width=100,height=50``

.. _resize:

resize
######

This transformation will resize the image. Two parameters are supported and at least one of them must be supplied to apply the transformation.

**Parameters:**

``width``
    The width of the resulting image in pixels. If not specified the width will be calculated using the same aspect ratio as the original image.

``height``
    The height of the resulting image in pixels. If not specified the height will be calculated using the same aspect ratio as the original image.

**Examples:**

* ``t[]=resize:width=100``
* ``t[]=resize:height=100``
* ``t[]=resize:width=100,height=50``

rotate
######

This transformation will rotate the image clock-wise.

**Parameters:**

``angle``
    The number of degrees to rotate the image (clock-wise).

``bg``
    Background color in hexadecimal. Defaults to ``000000`` (also supports short values like ``f00`` (``ff0000``)).

**Examples:**

* ``t[]=rotate:angle=90``
* ``t[]=rotate:angle=45,bg=fff``

sepia
#####

This transformation will apply a sepia color tone transformation to the image.

**Parameters:**

``threshold``
    Threshold ranges from 0 to QuantumRange and is a measure of the extent of the sepia toning. Defaults to ``80``

**Examples:**

* ``t[]=sepia``
* ``t[]=sepia:threshold=70``

thumbnail
#########

This transformation creates a thumbnail of ``<image>``.

**Parameters:**

``width``
    Width of the thumbnail in pixels. Defaults to ``50``.

``height``
    Height of the thumbnail in pixels. Defaults to ``50``.

``fit``
    Fit style. Possible values are: ``inset`` or ``outbound``. Default to ``outbound``.

**Examples:**

* ``t[]=thumbnail``
* ``t[]=thumbnail:width=20,height=20,fit=inset``

transpose
#########

This transformation transposes the image.

**Examples:**

* ``t[]=transpose``

transverse
##########

This transformation transverses the image.

**Examples:**

* ``t[]=transverse``

PUT /users/<user>/images/<image>
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Store a new image on the server.

The body of the response contains a JSON object containing the image identifier of the resulting image:

.. code-block:: bash

    $ curl -XPUT http://imbo/users/<user>/images/<checksum of file to add> --data-binary @<file to add>

results in:

.. code-block:: javascript

    {
      "imageIdentifier": "<image>"
    }

where ``<image>`` can be used to fetch the added image and apply transformations to it. The output from this method is important as the ``<image>`` in the response might not be the same as ``<checksum of file to add>`` in the URI in the above example (which might occur if for instance event listeners transform the image in some way before Imbo stores it).

**Typical response codes:**

* 200 OK
* 201 Created
* 400 Bad Request

DELETE /users/<user>/images/<image>
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Delete the image identified by ``<image>`` owned by ``<user>`` along with all metadata attached to the image.

.. code-block:: bash

    $ curl -XDELETE http://imbo/users/<user>/images/<image>

results in:

.. code-block:: javascript

    {
      "imageIdentifier": "<image>"
    }

where ``<image>`` is the image identifier of the image that was just deleted (the same as the one used in the URI).

**Typical response codes:**

* 200 OK
* 404 Not found

.. _metadata-resource:

Metadata resource
+++++++++++++++++

Imbo can also be used to attach metadata to the stored images. The metadata is based on a simple ``key => value`` model, for instance:

* ``category: Music``
* ``band: Koldbrann``
* ``genre: Black metal``
* ``country: Norway``

Metadata is handled via the ``meta`` resource in the URI, which is a sub-resource of ``<image>``.

GET /users/<user>/images/<image>/meta
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Get all metadata attached to ``<image>`` owned by ``<user>``. The output from Imbo is an empty list if the image has no metadata attached, or a JSON object with keys and values if metadata exists:

.. code-block:: bash

    $ curl http://imbo/users/<user>/images/<image>/meta.json

results in:

.. code-block:: javascript

    []

when there is not metadata, or for example

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
* 404 Not found

PUT /users/<user>/images/<image>/meta
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Replace all existing metadata attached to ``<image>`` owned by ``<user>`` with the metadata contained in a JSON object in the request body. The response body contains a JSON object with the image identifier:

.. code-block:: bash

    $ curl -XPUT http://imbo/users/<user>/images/<image>/meta.json -d '{
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

**Typical response codes:**

* 200 OK
* 400 Bad Request
* 404 Not found

POST /users/<user>/images/<image>/meta
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Edit existing metadata and/or add new keys/values to ``<image>`` owned by ``<user>`` with the metadata contained in a JSON object in the request body. The response body contains a JSON object with the image identifier:

.. code-block:: bash

    $ curl -XPOST http://imbo/users/<user>/images/<image>/meta.json -d '{
        "ABV":"16%",
        "score":"100/100"
    }'

results in:

.. code-block:: javascript

    {
      "imageIdentifier": "<image>"
    }

where ``<image>`` is the image that just got updated.

**Typical response codes:**

* 200 OK
* 400 Bad Request
* 404 Not found

DELETE /users/<user>/images/<image>/meta
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Delete all existing metadata attached to ``<image>`` owner by ``<user>``. The response body contains a JSON object with the image identifier:

.. code-block:: bash

    $ curl -XDELETE http://imbo/users/<user>/images/<image>/meta.json

results in:

.. code-block:: javascript

    {
      "imageIdentifier":"<image>"
    }

where ``<image>`` is the image identifier of the image that just got all its metadata deleted.

**Typical response codes:**

* 200 OK
* 400 Bad Request
* 404 Not found

Authentication
--------------

Imbo uses two types of authentication out of the box. It requires access tokens for all ``GET`` and ``HEAD`` requests made against all resources (with the exception of the status resource), and a valid request signature for all ``PUT``, ``POST`` and ``DELETE`` requests made against all resources that support these methods. Both mechanisms are enforced by event listeners that is enabled in the default configuration file.

.. _access-tokens:

Access tokens
+++++++++++++

Access tokens for all read requests are enforced by an event listener that is enabled per default. The access tokens are used to prevent `DoS`_ attacks so think twice (or maybe even some more) before you remove the listener. More about how to remove the listener in :ref:`configuration-event-listeners`.

The access token, when enforced, must be supplied in the URI using the ``accessToken`` query parameter and without it all ``GET`` and ``HEAD`` requests will result in a ``400 Bad Request`` response. The value of the ``accessToken`` parameter is a `Hash-based Message Authentication Code`_ (HMAC). The code is a hash of the URI itself (hashed with the `SHA-256`_ algorithm) using the private key of the user as the secret key. Below is an example on how to generate a valid access token for a specific image using PHP:

.. _SHA-256: http://en.wikipedia.org/wiki/SHA-2
.. _DoS: http://en.wikipedia.org/wiki/Denial-of-service_attack
.. _Hash-based Message Authentication Code: http://en.wikipedia.org/wiki/HMAC

.. literalinclude:: ../examples/generateAccessToken.php
    :language: php
    :linenos:

If you request a resource from Imbo without a valid access token it will respond with a ``400 Bad Request``. If the event listener enforcing the access token check is removed, Imbo will ignore the ``accessToken`` query parameter completely. If you wish to implement your own form of access token you can do this by implementing an event listener of your own (see :doc:`/advanced/custom_event_listeners` for more information).

.. _signing-write-requests:

Signing write requests
++++++++++++++++++++++

Imbo uses a similar method when authenticating write operations. To be able to write to Imbo the user agent will have to specify two request parameters: ``signature`` and ``timestamp``. ``signature`` is, like the access token, an HMAC (also using SHA-256 and the private key of the user). This code is generated using the following elements:

* HTTP method (``PUT``, ``POST`` or ``DELETE``)
* The URI
* Public key of the user
* GMT timestamp (``YYYY-MM-DDTHH:MM:SSZ``, for instance: ``2011-02-01T14:33:03Z``)

These elements are concatenated in the above order with ``|`` as a delimiter character, and a hash is generated using the private key of the user. The following snippet shows how this can be accomplished in PHP when deleting an image:

.. literalinclude:: ../examples/generateSignatureForDelete.php
    :language: php
    :linenos:

The above code will generate a signature that must be sent along the request using the ``signature`` query parameter. The timestamp used must also be provided using the ``timestamp`` query parameter so that the signature can be re-generated server-side. Imbo requires that the ``timestamp`` is within ± 120 seconds of the current time on the server. Both the ``signature`` and the ``timestamp`` query parameters must be URL-encoded.

As with the access token the signature check is enforced by an event listener that can also be disabled. If you want to implement your own authentication paradigm you can do this by creating a custom event listener.

Errors
------

When an error occurs Imbo will respond with a fitting HTTP response code along with a JSON object explaining what went wrong.

.. code-block:: bash

    $ curl "http://imbo/users/<user>/images/<image>.jpg?t\[\]=foobar"

results in:

.. code-block:: javascript

    {
      "error": {
        "code": 400,
        "message": "Unknown transformation: foobar",
        "date": "Wed, 12 Dec 2012 21:15:01 GMT",
        "imboErrorCode":0
      },
      "imageIdentifier": "<image>"
    }

The ``code`` is the HTTP response code, ``message`` is a human readable error message, ``date`` is when the error occurred on the server, and ``imboErrorCode`` is an internal error code that can be used by the user agent to distinguish between similar errors (such as ``400 Bad Request``).

The JSON object will also include ``imageIdentifier`` if the request was made against the image or the metadata resource.

If the user agent specifies a nonexistent username the following occurs:

.. code-block:: bash

    $ curl http://imbo/users/<user>.json

results in:

.. code-block:: javascript

    {
      "error": {
        "code": 404,
        "message": "Unknown public key",
        "date": "Mon, 13 Aug 2012 17:22:37 GMT",
        "imboErrorCode": 100
      }
    }

if ``<user>`` does not exist.
