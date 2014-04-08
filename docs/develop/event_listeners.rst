Working with events and event listeners
=======================================

Imbo uses an event dispatcher to trigger certain events from inside the application that you can subscribe to by using event listeners. In this chapter you can find information regarding the events that are triggered, and how to be able to write your own event listeners for Imbo.

Events
------

When implementing an event listener you need to know about the events that Imbo triggers. The most important events are combinations of the accessed resource along with the HTTP method used. Imbo currently provides these resources:

* :ref:`index <index-resource>`
* :ref:`stats <stats-resource>`
* :ref:`status <status-resource>`
* :ref:`user <user-resource>`
* :ref:`images <images-resource>`
* :ref:`image <image-resource>`
* :ref:`globalshorturl <global-shorturl-resource>`
* :ref:`metadata <metadata-resource>`

Examples of events that are triggered:

* ``image.get``
* ``images.post``
* ``image.delete``
* ``metadata.get``
* ``status.head``
* ``stats.get``

As you can see from the above examples the events are built up by the resource name and the HTTP method, lowercased and separated by ``.``.

Some other notable events:

* ``storage.image.insert``
* ``storage.image.load``
* ``storage.image.delete``
* ``db.image.insert``
* ``db.image.load``
* ``db.image.delete``
* ``db.metadata.update``
* ``db.metadata.load``
* ``db.metadata.delete``
* ``response.send``

Image transformations also use the event dispatcher when triggering events. The events triggered for this is prefixed with ``image.transformation.`` and ends with the transformation as specified in the URL, lowercased. If you specify ``t[]=thumbnail&t[]=flipHorizontally`` as a query parameter when requesting an image the following events will be triggered:

* ``image.transformation.thumbnail``
* ``image.transformation.fliphorizontally``

All image transformation events adds the image and parameters for the transformation as arguments to the event, which can be fetched by the transformation via the ``$event`` object passed to the methods which subscribe to the transformation events.

.. _custom-event-listeners:

Writing an event listener
-------------------------

When writing an event listener for Imbo you can choose one of the following approaches:

1) Implement the ``Imbo\EventListener\ListenerInterface`` interface that comes with Imbo
2) Implement a callable piece of code, for instance a class with an ``__invoke`` method
3) Use a `Closure <http://www.php.net/closure>`_

Below you will find examples on the approaches mentioned above.

.. note::
    Information regarding how to **attach** the event listeners to Imbo is available in the :ref:`event listener configuration <configuration-event-listeners>` section.

Implement the ``Imbo\EventListener\ListenerInterface`` interface
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Below is the complete interface with comments:

.. literalinclude:: ../../library/Imbo/EventListener/ListenerInterface.php
    :language: php
    :linenos:

The only method you need to implement is called ``getSubscribedEvents`` and that method should return an array where the keys are event names, and the values are callbacks. You can have several callbacks to the same event, and they can all have specific priorities.

Below is an example of how the :ref:`authenticate-event-listener` event listener implements the ``getSubscribedEvents`` method:

.. code-block:: php

    <?php

    // ...

    public static function getSubscribedEvents() {
        $callbacks = array();
        $events = array(
            'images.post',
            'image.delete',
            'metadata.put',
            'metadata.post',
            'metadata.delete'
        );

        foreach ($events as $event) {
            $callbacks[$event] = array('authenticate' => 100);
        }

        return $callbacks;
    }

    public function authenticate(Imbo\EventManager\EventInterface $event) {
        // Code that handles all events this listener subscribes to
    }

    // ...

In the snippet above the same method (``authenticate``) is attached to several events. The priority used is 100, which means it's triggered early in the application flow.

The ``authenticate`` method, when executed, receives an instance of :ref:`the event object <the-event-object>` that it can work with. The fact that the above code only uses a single callback for all events is an implementation detail. You can use different callbacks for all events if you want to.

Use a class with an ``__invoke`` method
+++++++++++++++++++++++++++++++++++++++

You can also keep the listener definition code out of the event listener entirely, and specify that piece of information in the Imbo configuration instead. An invokable class could for instance look like this:

.. code-block:: php

    <?php
    class SomeEventListener {
        public function __invoke(Imbo\EventManager\EventInterface $event) {
            // some custom code
        }
    }

where the ``$event`` object is the same as the one passed to the ``authenticate`` method in the previous example.

Use a Closure
+++++++++++++

For testing and/or debugging purposes you can also write the event listener directly in the configuration, by using a `Closure <http://www.php.net/closure>`_:

.. code-block:: php

    <?php
    return array(
        // ...

        'eventListeners' => array(
            'customListener' => array(
                'callback' => function(Imbo\EventManager\EventInterface $event) {
                    // Custom code
                },
                'events' => array('image.get'),
            ),
        ),

        // ...
    );

The ``$event`` object passed to the function is the same as in the previous two examples. This approach should mostly be used for testing purposes and quick hacks. More information regarding this approach is available in the :ref:`event listener configuration <configuration-event-listeners>` section.


.. _the-event-object:

The event object
----------------

The object passed to the event listeners is an instance of the ``Imbo\EventManager\EventInterface`` interface. This interface has some methods that event listeners can use:

``getName()``
    Get the name of the current event. For instance ``image.delete``.

``getHandler()``
    Get the name of the current event handler, as specified in the configuration. Can come in handy when you have to dynamically register more callbacks based on constructor parameters for the event listener. Have a look at the implementation of :ref:`the CORS event listener <cors-event-listener>` for an example on how to achieve this.

``getRequest()``
    Get the current request object (an instance of ``Imbo\Http\Request\Request``)

``getResponse()``
    Get the current response object (an instance of ``Imbo\Http\Response\Response``)

``getDatabase()``
    Get the current database adapter (an instance of ``Imbo\Database\DatabaseInterface``)

``getStorage()``
    Get the current storage adapter (an instance of ``Imbo\Storage\StorageInterface``)

``getManager()``
    Get the current event manager (an instance of ``Imbo\EventManager\EventManager``)

``getConfig()``
    Get the complete Imbo configuration. This should be used with caution as it includes all authentication information regarding the Imbo users.

``stopPropagation()``
    If you want your event listener to force Imbo to skip all following listeners for the same event, call this method in your listener.

``isPropagationStopped()``
    This method is used by Imbo to check if a listener wants the propagation to stop. Your listener will most likely never need to use this method.

``getArgument()``
    This method can be used to fetch arguments given to the event. This method is used by all image transformation event listeners as the image itself and the parameters for the transformation is stored as arguments to the event.

With these methods you have access to most parts of Imbo. Be careful when using the database and storage adapters as these grant you access to all data stored in Imbo, with both read and write permissions.
