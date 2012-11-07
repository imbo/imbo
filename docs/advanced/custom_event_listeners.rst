.. _custom-event-listeners:

Custom event listeners
======================

If you wish to implement your own event listeners you are free to do so. The only requirement is that you implement the ``Imbo\EventListener\ListenerInterface`` interface that comes with Imbo. Below is the complete interface with comments:

.. literalinclude:: ../../library/Imbo/EventListener/ListenerInterface.php
    :language: php
    :linenos:

Have a look at the existing implementations of this interface for more details. If you implement a listener that you think should be a part of Imbo feel free to send a pull request to the `project over at GitHub`_.

.. _project over at GitHub: https://github.com/imbo/imbo

There is also an abstract implementation of the interface above that implement the ``setPublicKeys()`` and ``getPublicKeys()`` methods:

.. literalinclude:: ../../library/Imbo/EventListener/Listener.php
    :language: php
    :linenos:

Extend this class if you wish to skip copy/pasting these methods.

Event listeners can also have custom methods for each event they subscribe to. If you for instance subscribe to ``image.get.pre`` and ``image.get.post`` your listener can implement the following methods:

* ``onImageGetPre``
* ``onImageGetPost``

This way listeners does not have to check the name of the current event in the ``invoke`` method, making it easier to subscribe to several events. These custom methods will receive an instance of ``Imbo\EventManager\EventInterface``, just like the ``invoke`` method.
