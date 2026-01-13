Imbo
====

Imbo is an application that can be used to add/get/delete images using a `RESTful`_ HTTP API. There is also support for adding meta data to the images stored in Imbo. The main idea behind Imbo is to have a place to store high quality original images and to use the API to fetch variations of the images. Imbo will resize, rotate and crop (amongst other transformations) images on the fly so you won't have to store all the different variations.

Imbo is written in `PHP`_, licensed with the (`MIT license`_), and hosted on `GitHub`_. If you find any issues or missing features please add an issue in the `Imbo issue tracker`_.

.. _RESTful: https://en.wikipedia.org/wiki/REST
.. _MIT license: https://opensource.org/licenses/MIT
.. _PHP: https://php.net
.. _GitHub: https://github.com/imbo/imbo
.. _Imbo issue tracker: https://github.com/imbo/imbo/issues

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
