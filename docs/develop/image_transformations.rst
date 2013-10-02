.. _custom-image-transformations:

Implement your own image transformations
========================================

Imbo also supports custom image transformations. All you need to do is to implement the ``Imbo\Image\Transformation\TransformationInterface`` interface, and configure your transformation:

.. code-block:: php

    <?php
    return array(
        // ..

        'imageTransformations' => array(
            'border' => 'My\Custom\BorderTransformation',
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
