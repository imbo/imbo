.. _custom-image-transformations:

Implement your own image transformations
========================================

Imbo also supports custom image transformations. All you need to do is to implement the ``Imbo\Image\Transformation\TransformationInterface`` interface, or specify a callable piece of code in the :ref:`image transformation configuration <image-transformations-config>`. Below is an implementation of the border transformation as a callable piece of code:

.. code-block:: php

    <?php
    return array(
        // ...

        'imageTransformations' => array(
            'border' => function (array $params) {
                return function (Imbo\Model\Image $image) use ($params) {
                    $color = !empty($params['color']) ? $params['color'] : '#000';
                    $width = !empty($params['width']) ? $params['width'] : 1;
                    $height = !empty($params['height']) ? $params['height'] : 1;

                    try {
                        $imagick = new Imagick();
                        $imagick->readImageBlob($image->getBlob());
                        $imagick->borderImage($color, $width, $height);

                        $size = $imagick->getImageGeometry();

                        $image->setBlob($imagick->getImageBlob())
                              ->setWidth($size['width'])
                              ->setHeight($size['height']);
                    } catch (ImagickException $e) {
                        throw new Imbo\Image\Transformation\TransformationException($e->getMessage(), 400, $e);
                    }
                };
            },
        ),

        // ...
    );

It's not recommended to use this method for big complicated transformations. It's better to implement the interface mentioned above, and refer to your class in the configuration array instead:

.. code-block:: php

    <?php
    return array(
        // ..

        'imageTransformations' => array(
            'border' => function (array $params) {
                return new My\Custom\BorderTransformation($params);
            },
        ),

        // ...
    );

where ``My\Custom\BorderTransformation`` implements ``Imbo\Image\Transformation\TransformationInterface``.

Image transformation presets/collections
----------------------------------------

If you want to combine some of the existing image transformations you can use the ``Imbo\Image\Transformation\Collection`` transformation for this purpose. The constructor takes an array of other transformations:

.. code-block:: php

    <?php
    use Imbo\Image\Transformation;

    return array(
        // ...

        'imageTransformations' => array(
            'thumb' => function ($params) {
                return new Transformation\Collection(array(
                    new Transformation\Desaturate(),
                    new Transformation\Thumbnail(array(
                        'width' => 60,
                        'height' => 60,
                    )),
                    new Transformation\Border(array(
                        'width' => 2,
                        'height' => 2,
                        'mode' => 'inline',
                    )),
                ));
            },
        ),

        // ...
    );

When images are requested with the ``t[]=thumb`` query parameter they will first be desaturated, then made into a 60 x 60 pixel thumbnail and last they will get a 2 pixel border painted inside of the thumbnail, maintaining the 60 x 60 pixel size.
