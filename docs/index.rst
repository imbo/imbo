Imbo
====

Imbo is an application that can add/get/delete/transform images using a `RESTful interface <https://en.wikipedia.org/wiki/REST>`_.

The main idea behind Imbo is to have a place to store high quality original images and to use the REST interface to fetch variations of those images. Imbo will resize, rotate, crop (amongst many other transformations) on the fly so you won't have to store all the different variations.

Imbo is written in `PHP <https://php.net>`_, licensed with the `MIT license <https://opensource.org/licenses/MIT>`_, and hosted on `GitHub <https://github.com/imbo/imbo>`_. If you find any issues or missing features please add an issue in the `Imbo issue tracker <https://github.com/imbo/imbo/issues>`_.

Installation guide
------------------
.. toctree::
    :maxdepth: 2

    installation/requirements
    installation/installation
    installation/upgrading
    installation/configuration
    installation/event_listeners
    installation/cli

End user guide
--------------
.. toctree::
    :maxdepth: 3

    usage/api
    usage/image-transformations

Extending/customizing Imbo
--------------------------
.. toctree::
    :maxdepth: 2

    develop/event_listeners
    develop/image_transformations
    develop/contributing
