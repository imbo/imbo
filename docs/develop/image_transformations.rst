.. _custom-image-transformations:

Implement your own image transformations
========================================

Imbo also supports custom image transformations. All you need to do is to create an event listener, and configure your transformation:

.. code-block:: php

    <?php
    class My\Custom\Transformation implements Imbo\EventListener\ListenerInterface {
        public static function getSubscribedEvents() {
            return ['image.transformation.cooltransformation' => 'transform'];
        }

        public function transform($event) {
            $image = $event->getArgument('image');
            $params = $event->getArgument('params'); // If the transformation allows params in the URL

            // ...
        }
    }

    return [
        // ..

        'eventListeners' => [
            'coolTransformation' => 'My\Custom\Transformation',
        ],

        // ...
    ];

Whenever someone requests an image using ``?t[]=coolTransformation:width=100,height=200`` Imbo will trigger the ``image.transformation.cooltransformation`` event, and assign the following value to the ``params`` argument of the event:

.. code-block:: php

    [
        'width' => '100',
        'height' => '200',
    ]

Take a look at the existing transformations included with Imbo for more information.
