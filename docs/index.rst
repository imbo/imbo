Imbo
====

Imbo is an image "server" that can be used to add/get/delete images using a RESTful HTTP API. There is also support for adding meta data to the images stored in Imbo. The main idea behind Imbo is to have a place to store high quality original images and to use the API to fetch variations of the images. Imbo will resize, rotate and crop (amongst other transformations) images on the fly so you won't have to store all the different variations.

Imbo is an open source (`MIT license`_) project written in `PHP`_ and is available on `GitHub`_. If you find any issues or missing features please add an issue in the `Imbo issue tracker`_. If you want to know more feel free to join the #imbo channel on the `Freenode IRC network`_ (chat.freenode.net) as well.

.. _MIT license: https://opensource.org/licenses/MIT
.. _PHP: https://php.net
.. _GitHub: https://github.com/imbo/imbo
.. _Imbo issue tracker: https://github.com/imbo/imbo/issues
.. _Freenode IRC Network: https://freenode.net

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
    develop/custom_adapters
    develop/image_transformations
    develop/contributing
