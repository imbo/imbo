Imbo's API
==========

In this chapter you will learn more about how Imbo's API works, and how you as a user is able to read from and write to Imbo. Most examples used in this chapter will use `cURL <http://curl.haxx.se/>`_, so while playing around with the API it's encourages to have cURL easily available. For the sake of simplicity the access tokens and signature information is not used in the examples. See the :ref:`authentication-and-access-tokens` section for more information regarding this.

Resources/endpoints
-------------------

In this section you will find information on the different resources Imbo's RESTful API expose, along with their capabilities:

.. contents:: Available resources
    :local:
    :depth: 1

.. _stats-resource:

Stats resource
++++++++++++++
Imbo provides an endpoint for fetching simple statistics about the data stored in Imbo.

.. code-block:: bash

    $ curl http://imbo/stats.json

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

Access control
~~~~~~~~~~~~~~

The access control for the stats endpoint is controlled by an :ref:`event listener <stats-access>`, which is enabled per default, and only allows connections from ``127.0.0.1``.

Custom statistics
~~~~~~~~~~~~~~~~~

The stats resource lets users attach custom data via event listeners by using the model as a regular associative array. The following example attaches a simple event listener in the configuration file that populates some custom data in the statistics model:

.. code-block:: php
    :linenos:

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
        }
      }
    }

Use cases for this might be to simply store data in some backend in various events (for instance ``image.get`` or ``metadata.get``) and then fetch these and display then when requesting the stats endpoint (``stats.get``).

.. _status-resource:

Status resource
+++++++++++++++

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

where ``timestamp`` is the current timestamp on the server, and ``database`` and ``storage`` are boolean values informing of the status of the current database and storage adapters respectively. If both are ``true`` the HTTP status code is ``200 OK``, and if one or both are ``false`` the status code is ``500``. When the status code is ``500`` the status message will inform you whether it's the database or the storage adapter (or both) that is having issues.

**Supported HTTP methods:**

* GET

**Typical response codes:**

* 200 OK
* 500 Database error
* 500 Storage error
* 500 Storage and database error

.. _user-resource:

User resource
+++++++++++++

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

where ``publicKey`` is the public key of the user, ``numImages`` is the number of images the user has stored in Imbo and ``lastModified`` is when the user last uploaded an image or updated metadata of an image.

**Supported HTTP methods:**

* GET

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

    curl http://imbo/users/<user>/images/<image>

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

.. _border-transformation:

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

``mode``
    Mode of the border. Can be ``inline`` or ``outbound``. Defaults to ``outbound``. Outbound places the border outside of the image, increasing the dimensions of the image. ``inline`` paints the border inside of the image, retaining the original width and height of the image.

**Examples:**

* ``t[]=border``
* ``t[]=border:mode=inline``
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

It is not possible to explicitly trigger this transformation via the ``t[]`` query parameter.

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

watermark
#########

This transformation can be used to apply a watermark on top of the original image.

**Parameters:**

``img``
    Image identifier of the image to apply as watermark. Can be set to a default value in configuration by using ``<setDefaultImage>``.

``width``
    Width of the watermark image in pixels. If omitted the width of ``<img>`` will be used.

``height``
    Height of the watermark image in pixels. If omitted the height of ``<img>`` will be used.

``position``
    The placement of the watermark image. ``top-left``, ``top-right``, ``bottom-left``, ``bottom-right`` and ``center`` are available values. Defaults to ``top-left``.

``x``
    Number of pixels in the X-axis the watermark image should be offset from the original position (defined by the ``position`` parameter). Supports negative numbers. Defaults to ``0``

``y``
    Number of pixels in the Y-axis the watermark image should be offset from the original position (defined by the ``position`` parameter). Supports negative numbers. Defaults to ``0``

**Examples:**

* ``t[]=watermark:img=f5f7851c40e2b76a01af9482f67bbf3f``
* ``t[]=watermark:img=f5f7851c40e2b76a01af9482f67bbf3f,width=200,x=5``
* ``t[]=watermark:img=f5f7851c40e2b76a01af9482f67bbf3f,height=50,x=-5,y=-5,position=bottom-right``

If you want to set the default watermark image you will have to do so in the configuration:

.. code-block:: php

    <?php
    return array(
        // ...

        'imageTransformations' => array(
            'watermark' => function (array $params) {
                $transformation = new Imbo\Image\Transformation\Watermark($params);
                $transformation->setDefaultImage('some image identifier');

                return $transformation;
            },
        ),

        // ...
    );

When you have specified a default watermark image you are not required to use the ``img`` option for the transformation, but if you do so it will override the default one.

PUT /users/<user>/images/<image>
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Store a new image on the server.

The body of the response contains a JSON object containing the image identifier of the resulting image:

.. code-block:: bash

    curl -XPUT http://imbo/users/<user>/images/<checksum of file to add> --data-binary @<file to add>

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

    curl -XDELETE http://imbo/users/<user>/images/<image>

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

    curl http://imbo/users/<user>/images/<image>/meta.json

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

    curl -XPUT http://imbo/users/<user>/images/<image>/meta.json -d '{
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

    curl -XPOST http://imbo/users/<user>/images/<image>/meta.json -d '{
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

    curl -XDELETE http://imbo/users/<user>/images/<image>/meta.json

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

.. _authentication-and-access-tokens:

Authentication and access tokens
--------------------------------

Imbo uses a RESTful API to manage the content stored in Imbo. To be able to fetch content (HTTP ``GET`` and ``HEAD``) each request must include an access token, and when writing to Imbo (HTTP ``PUT``, ``POST`` and ``DELETE``) the request needs to be signed with a signature and a timestamp. The default configuration enforces this via two event listeners: :ref:`access-token-event-listener` and :ref:`authenticate-event-listener`.

.. _access-tokens:

Access tokens
+++++++++++++

Access tokens are enforced by an event listener that is enabled in the default configuration file. The access tokens are used to prevent `DoS <http://en.wikipedia.org/wiki/Denial-of-service_attack>`_ attacks so think twice before you disable the listener.

An access token, when enforced by the listener, must be supplied in the URI using the ``accessToken`` query parameter and without it all ``GET`` and ``HEAD`` requests will result in a ``400 Bad Request`` response. The value of the ``accessToken`` parameter is a `Hash-based Message Authentication Code <http://en.wikipedia.org/wiki/HMAC>`_ (HMAC). The code is a `SHA-256 <http://en.wikipedia.org/wiki/SHA-2>`_ hash of the URI itself using the private key of the user as the secret key. It is very important that the URI is not URL encoded when generating the hash. Below is an example on how to generate a valid access token for a specific image using PHP:

.. literalinclude:: ../examples/generateAccessToken.php
    :language: php
    :linenos:

If the event listener enforcing the access token check is removed, Imbo will ignore the ``accessToken`` query parameter completely. If you wish to implement your own form of access token you can do this by implementing an event listener of your own (see :doc:`../advanced/custom_event_listeners` for more information).

.. _signing-write-requests:

Signing write requests
++++++++++++++++++++++

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

Imbo requires that ``X-Imbo-Authenticate-Timestamp`` is within ± 120 seconds of the current time on the server.

As with the access token the signature check is enforced by an event listener that can also be disabled. If you disable this event listener you effectively open up for writing from anybody, which you probably don't want to do.

If you want to implement your own authentication paradigm you can do this by creating a custom event listener.

Supported content types
-----------------------

Imbo currently responds with images (jpg, gif and png), `JSON <http://en.wikipedia.org/wiki/JSON>`_ and `XML <http://en.wikipedia.org/wiki/XML>`_, but only accepts images (jpg, gif and png) and JSON as input.

Imbo will do content negotiation using the `Accept <http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html>`_ header found in the request, unless you specify a file extension, in which case Imbo will deliver the type requested without looking at the Accept header.

The default Content-Type for non-image responses is JSON, and for most examples in this document you will see the ``.json`` extension being used. Change that to ``.xml`` to get XML data. You can also skip the extension and force a specific Content-Type using the Accept header:

.. code-block:: bash

    curl http://imbo/status.json

and

.. code-block:: bash

    curl -H "Accept: application/json" http://imbo/status

will end up with the same content-type. Use ``application/xml`` for XML.

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

HTTP response codes
-------------------

Errors
------

When an error occurs Imbo will respond with a fitting HTTP response code along with a JSON object explaining what went wrong.

.. code-block:: bash

    curl "http://imbo/users/<user>/images/<image>.jpg?t\[\]=foobar"

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

    curl http://imbo/users/<user>.json

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
