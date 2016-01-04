.. _image-transformations:

Transforming images on the fly
==============================

What you as an end-user of an Imbo installation will be doing most of the time, is working with images. This is what Imbo was originally made for, and this chapter includes details about all the different image transformations Imbo supports.

All image transformations can be triggered by specifying the ``t`` query parameter. This parameter must be used as an array so that you can provide several image transformations. The transformations will be applied to the image in the same order as they appear in the URL. Each element in this array represents a single transformation with optional parameters, specified as a string. If the ``t`` query parameter is not an array or if any of its elements are not strings, Imbo will respond with ``HTTP 400``.

Below you will find all image transformations supported "out of the box", along with their parameters. Some transformations are rarely used with ``HTTP GET``, but are instead used by event listeners that transform images when they are added to Imbo (``HTTP POST``). If this is the case it will be mentioned in the description of the transformation.

.. _auto-rotate-transformation:

Auto rotate image based on EXIF data - ``t[]=autoRotate``
---------------------------------------------------------

This transformation will auto rotate the image based on EXIF data stored in the image. This transformation is rarely used per request, but is typically used by the :ref:`auto-rotate-image-event-listener` event listener when adding images to Imbo.

**Examples:**

* ``t[]=autoRotate``

.. _blur-transformation:

Blur the image - ``t[]=blur``
-----------------------------------

This transformation can be used to blur the image.

**Parameters:**

``mode``
    The blur type (optional). Defaults to ``gaussian``. Possible values are:

    ``gaussian``
        When adding gaussian blur, the ``radius`` and ``sigma`` parameters are required.

    ``adaptive``
        When adding adaptive blur, the ``radius`` and ``sigma`` parameters are required. Adaptive blur decrease the blur in the part of the picture near to the edge of the image canvas.

    ``motion``
        When adding motion blur, the ``radius``, ``sigma`` and ``angle`` parameters are required.

    ``radial``
        When adding radial blur, the ``angle`` parameter is required.

``radius``
    The radius of the Gaussian, in pixels, not counting the center pixel.

``sigma``
    The standard deviation of the Gaussian, in pixels.

``angle``
    The number of degrees to rotate the image.

**Examples:**

* ``t[]=blur:radius=1,sigma=2``
* ``t[]=blur:type=adaptive,radius=2,sigma=4``

.. _border-transformation:

Add an image border - ``t[]=border``
------------------------------------

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

.. _canvas-transformation:

Expand the image canvas - ``t[]=canvas``
----------------------------------------

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

.. _compress-transformation:

Compress the image - ``t[]=compress``
-------------------------------------

This transformation compresses images on the fly resulting in a smaller payload. It is advisable to only use this transformation in combination with an image type in the URL (for instance ``.jpg`` or ``.png``). This transformation is not applied to images of type ``image/gif``.

**Parameters:**

``level``
    The level of the compression applied to the image. The effect this parameter has on the image depends on the type of the image. If the image in the response is an ``image/jpeg`` a high ``level`` means high quality, usually resulting in larger files. If the image in the response is an ``image/png`` a high ``level`` means high compression, usually resulting in smaller files. If you do not specify an image type in the URL the result of this transformation is not deterministic as clients have different preferences with regards to the type of images they want to receive (via the ``Accept`` request header).

**Examples:**

* ``t[]=compress:level=40``

.. _contrast-transformation:

Change image contrast - ``t[]=contrast``
----------------------------------------

This transformation can be used to change the contrast of the colors in the image.

**Parameters:**

``alpha``
    Used to adjust the intensity differences between the lighter and darker elements of the image. Can also be negative. Note: this parameter was named ``sharpen`` in Imbo 1.x.

``beta``
    Where the midpoint of the gradient will be. This value should be in the range 0 to 1. Default: ``0.5``.

**Examples:**

* ``t[]=contrast:alpha=3``

.. note:: If you are getting different results than expected when using negative ``alpha`` values, your ``imagick`` extension is probably built against an old version of ImageMagick.

.. _convert-transformation:

Convert the image type - ``.jpg/.gif/.png``
-------------------------------------------

This transformation can be used to change the image type. It is not applied like the other transformations, but is triggered when specifying a custom extension to the ``<image>``. Currently Imbo can convert to:

* ``image/jpeg``
* ``image/png``
* ``image/gif``

**Examples:**

* ``curl http://imbo/users/<user>/images/<image>.gif``
* ``curl http://imbo/users/<user>/images/<image>.jpg``
* ``curl http://imbo/users/<user>/images/<image>.png``

.. _crop-transformation:

Crop the image - ``t[]=crop``
-----------------------------

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

``mode``
    The crop mode (optional). Possible values are:

    ``center``
        When using the center mode the ``x`` and ``y`` parameters are ignored, and the center of the cropped area is placed in the center of the original image.

    ``center-x``
        Center the crop on the x-axis. Use the ``y`` parameter to control the upper edge of the crop.

    ``center-y``
        Center the crop on the y-axis. Use the ``x`` parameter to control the left edge of the crop.

**Examples:**

* ``t[]=crop:x=10,y=25,width=250,height=150``
* ``t[]=crop:width=100,height=100,mode=center``
* ``t[]=crop:width=50,height=50,mode=center-x,y=15``
* ``t[]=crop:width=50,height=50,mode=center-y,x=15``

.. _desaturate-transformation:

Make a gray scaled image - ``t[]=desaturate``
---------------------------------------------

This transformation desaturates the image (in practice, gray scales it).

**Examples:**

* ``t[]=desaturate``

.. _drawpois-transformation:

Draw points of interest - ``t[]=drawPois``
------------------------------------------

This transformation will draw an outline around all the POIs (points of interest) stored in the metadata for the image. The format of the metadata is documented under the :ref:`smartSize <smartsize-transformation>` transformation.

**Parameters:**

``color``
    Color of the border in hexadecimal format. Defaults to ``ff0000`` (You can also specify short values like ``f0f`` (``ff00ff``)).

``borderSize``
    Width of the border in pixels. Defaults to ``2``.

``pointSize``
    The diameter (in pixels) of the circle drawn around points of interest that do not have a height and width specified. Defaults to ``30``.

**Examples:**

* ``t[]=drawPois``
* ``t[]=drawPois:borderSize=10``
* ``t[]=drawPois:color=0f0``
* ``t[]=drawPois:color=00f,borderSize=10,pointSize=100``

.. note:: This transformation has a bug/limitation: all coordinates are based on the original image. In other words, applying this at the end of a transformation chain which resizes/crops/rotates the image can lead to unexpected results. This will hopefully change in the future.

.. _flip-horizontally-transformation:

Make a mirror image - ``t[]=flipHorizontally``
----------------------------------------------

This transformation flips the image horizontally.

**Examples:**

* ``t[]=flipHorizontally``

.. _flip-vertically-transformation:

Flip the image upside down - ``t[]=flipVertically``
---------------------------------------------------

This transformation flips the image vertically.

**Examples:**

* ``t[]=flipVertically``

.. _histogram-transformation:

Generate a histogram of the image - ``t[]=histogram``
-----------------------------------------------------

This transformation will convert the image into a histogram of the image itself, with a size of 256x158 pixels. The size of the generated image can be overridden by using one or more of the supported parameters.

**Parameters:**

``scale``
    The amount to scale the histogram. Defaults to ``1``.

``ratio``
    The ratio to use when calculating the height of the image. Defaults to ``1.618``.

``red``
    The color to use when drawing the graph for the red channel. Defaults to ``#D93333``.

``green``
    The color to use when drawing the graph for the green channel. Defaults to ``#58C458``.

``blue``
    The color to use when drawing the graph for the blue channel. Defaults to ``#3767BF``.

**Examples:**

* ``t[]=histogram``
* ``t[]=histogram:scale=2``
* ``t[]=histogram:red=f00,green=0f0,blue=00f``

.. _levels-transformation:

Adjust levels of the image - ``t[]=level``
-----------------------------------------------------

This transformation will adjust the levels of an image. You are able to specify individual channels to adjust - by default it will apply to all channels.

**Parameters:**

``channel``
    The channel to adjust. ``r`` (red), ``g`` (green), ``b`` (blue), ``c`` (cyan), ``m`` (magenta), ``y`` (yellow), ``k`` (black) and ``all`` (all channels) are available values. These channels can also be combined, if multiple channels should be adjusted. Defaults to ``all``.

``amount``
    The amount to adjust by. Range is from ``-100`` to ``100``. Defaults to ``1``.

**Examples:**

* ``t[]=level``
* ``t[]=level:channel=r,amount=30``
* ``t[]=level:channel=rg,amount=-45``

.. _max-size-transformation:

Enforce a max size of an image - ``t[]=maxSize``
------------------------------------------------

This transformation will resize the image using the original aspect ratio. Two parameters are supported and at least one of them must be supplied to apply the transformation.

Note the difference from the :ref:`resize <resize-transformation>` transformation: given both ``width`` and ``height``, the resulting image will not be the same width and height as specified unless the aspect ratio is the same.

**Parameters:**

``width``
    The max width of the resulting image in pixels. If not specified the width will be calculated using the same aspect ratio as the original image.

``height``
    The max height of the resulting image in pixels. If not specified the height will be calculated using the same aspect ratio as the original image.

**Examples:**

* ``t[]=maxSize:width=100``
* ``t[]=maxSize:height=100``
* ``t[]=maxSize:width=100,height=50``

.. _modulate-transformation:

Modulate the image - ``t[]=modulate``
-------------------------------------

This transformation can be used to control the brightness, saturation and hue of the image.

**Parameters:**

``b``
    Brightness of the image in percent. Defaults to 100.

``s``
    Saturation of the image in percent. Defaults to 100.

``h``
    Hue percentage. Defaults to 100.

**Examples:**

* ``t[]=modulate:b=150``
* ``t[]=modulate:b=120,s=130,h=90``

.. _progressive-transformation:

Make a progressive image - ``t[]=progressive``
----------------------------------------------

This transformation makes the image progressive.

**Examples:**

* ``t[]=progressive``

.. _resize-transformation:

Resize the image - ``t[]=resize``
---------------------------------

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

.. _rotate-transformation:

Rotate the image - ``t[]=rotate``
---------------------------------

This transformation will rotate the image clock-wise.

**Parameters:**

``angle``
    The number of degrees to rotate the image (clock-wise).

``bg``
    Background color in hexadecimal. Defaults to ``000000`` (also supports short values like ``f00`` (``ff0000``)).

**Examples:**

* ``t[]=rotate:angle=90``
* ``t[]=rotate:angle=45,bg=fff``

.. _sepia-transformation:

Apply a sepia color tone - ``t[]=sepia``
----------------------------------------

This transformation will apply a sepia color tone transformation to the image.

**Parameters:**

``threshold``
    Threshold ranges from 0 to QuantumRange and is a measure of the extent of the sepia toning. Defaults to ``80``

**Examples:**

* ``t[]=sepia``
* ``t[]=sepia:threshold=70``

.. _sharpen-transformation:

Sharpen the image - ``t[]=sharpen``
-----------------------------------

This transformation can be used to change the sharpness in the image.

**Parameters:**

``radius``
    The radius of the Gaussian operator in pixels. Defaults to ``2``.

``sigma``
    The standard deviation of the Gaussian, in pixels. Defaults to ``1``.

``gain``
    The percentage of the difference between the original and the blur image that is added back into the original. Defaults to ``1``.

``threshold``
    The threshold in pixels needed to apply the difference gain. Defaults to ``0.05``.

``preset``
    Different presets that can be used. The presets are:

    * ``light`` (radius = 2, sigma = 1, gain = 1, threshold = 0.05)
    * ``moderate`` (radius = 2, sigma = 1, gain = 2, threshold = 0.05)
    * ``strong`` (radius = 2, sigma = 1, gain = 3, threshold = 0.025)
    * ``extreme`` (radius = 2, sigma = 1, gain = 4, threshold = 0)

When using any of the presets the different parameters can be overridden by specifying ``radius``, ``sigma``, ``gain`` and/or ``threshold``. Not specifying any parameters at all is the same as using the ``light`` preset.

**Examples:**

* ``t[]=sharpen``
* ``t[]=sharpen:preset=light`` (same as above)
* ``t[]=sharpen:preset=extreme,gain=10`` (use the ``extreme`` preset, but use a gain value of 10 instead of 4)
* ``t[]=sharpen:radius=2,sigma=1,gain=1,threshold= 0.05`` (same as using ``t[]=sharpen:preset=light``, or simply ``t[]=sharpen``)

.. _smartsize-transformation:

Smart size the image - ``t[]=smartSize``
----------------------------------------

This transformation is used to crop the image based on a point of interest (POI) provided either as a transformation parameter or from the image metadata.

**Metadata format**

The smart size transformation supports reading the POI from the metadata of the image. The POI information is expected to be stored on the ``poi`` property in metadata. Below is an example of a valid metadata object containing a ``600,240`` POI:

.. code-block:: javascript

    {
      "poi": [
        {
            x: 600,
            y: 240
        }
      ]
    }

.. note:: The smart size transformation currently takes only the first object into account when cropping the image, but the POIs is stored as an array of objects in order to be easy to expand with more information for a more sophisticated smart size algorithm in the future.

**Parameters:**

``width``
    The width of the crop in pixels.

``height``
    The height of the crop in pixels.

``poi``
    The POI coordinate ``x,y`` to crop around. The parameter is optional if the POI exists in metadata.

``crop``
    The closeness of the crop (optional). Possible values are:

    ``close``
    ``medium``
    ``wide``

**Examples:**

* ``t[]=smartSize:width=250,height=250,poi=300,200``
* ``t[]=smartSize:width=250,height=250,poi=300,200,crop=close``

.. _strip-transformation:

Strip image properties and comments - ``t[]=strip``
---------------------------------------------------

This transformation removes all properties and comments from the image. If you want to strip EXIF tags from the image for instance, this transformation will do that for you.

**Examples:**

* ``t[]=strip``

.. _thumbnail-transformation:

Create a thumbnail of the image - ``t[]=thumbnail``
---------------------------------------------------

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

.. _transpose-transformation:

Create a vertical mirror image - ``t[]=transpose``
--------------------------------------------------

This transformation transposes the image.

**Examples:**

* ``t[]=transpose``

.. _transverse-transformation:

Create a horizontal mirror image - ``t[]=transverse``
-----------------------------------------------------

This transformation transverses the image.

**Examples:**

* ``t[]=transverse``

.. _vignette-transformation:

Add a vignette to the image - ``t[]=vignette``
----------------------------------------------

This transformation can be used to add a vignette to the image.

**Parameters:**

``inner``
    Color at the center of the image, in hexadecimal. Defaults to ``none``, which means transparent. (You can also specify short values like ``f00`` (``ff0000``)).

``outer``
    Color at the edge of the image, in hexadecimal. Defaults to ``000``.

``scale``
    Scale factor of the vignette. ``2`` will create a vignette twice the size of the original image. Defaults to ``1.5``.

**Examples:**

* ``t[]=vignette``
* ``t[]=vignette:outer=ccc``
* ``t[]=vignette:scale=1,outer=333``

.. _watermark-transformation:

Add a watermark to the image - ``t[]=watermark``
------------------------------------------------

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

``opacity``
    Can be an integer between 0 and 100 where 0 is fully transparent, and 100 is fully opaque. Defaults to ``100``

**Examples:**

* ``t[]=watermark:img=f5f7851c40e2b76a01af9482f67bbf3f``
* ``t[]=watermark:img=f5f7851c40e2b76a01af9482f67bbf3f,width=200,x=5``
* ``t[]=watermark:img=f5f7851c40e2b76a01af9482f67bbf3f,height=50,x=-5,y=-5,position=bottom-right,opacity=50``

If you want to set the default watermark image you will have to do so in the configuration:

.. code-block:: php

    <?php
    return [
        // ...

        'eventListeners' => [
            'watermark' => function() {
                $transformation = new Imbo\Image\Transformation\Watermark();
                $transformation->setDefaultImage('some image identifier');

                return $transformation;
            },
        ],

        // ...
    ];

When you have specified a default watermark image you are not required to use the ``img`` option for the transformation, but if you do so it will override the default one.
