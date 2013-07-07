.. _custom-image-transformations:

Custom image transformations
============================

You can also implement your own transformations by implementing the ``Imbo\Image\Transformation\TransformationInterface`` interface, or by specifying a callable piece of code. An implementation of the border transformation as a callable piece of code could for instance look like this:

.. code-block:: php

    <?php
    namespace Imbo;

    return array(
        // ...

        'imageTransformations' => array(
            'border' => function (array $params) {
                return function (Model\Image $image) use ($params) {
                    $color = !empty($params['color']) ? $params['color'] : '#000';
                    $width = !empty($params['width']) ? $params['width'] : 1;
                    $height = !empty($params['height']) ? $params['height'] : 1;

                    try {
                        $imagick = new \Imagick();
                        $imagick->readImageBlob($image->getBlob());
                        $imagick->borderImage($color, $width, $height);

                        $size = $imagick->getImageGeometry();

                        $image->setBlob($imagick->getImageBlob())
                              ->setWidth($size['width'])
                              ->setHeight($size['height']);
                    } catch (\ImagickException $e) {
                        throw new Image\Transformation\TransformationException($e->getMessage(), 400, $e);
                    }
                };
            },
        ),

        // ...
    );

It's not recommended to use this method for big complicated transformations. It's better to implement the interface mentioned above, and refer to your class in the configuration array instead:

.. code-block:: php

    <?php
    namespace Imbo;

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
