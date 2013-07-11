Imbo - Image box
================

Imbo is an image "server" that can be used to add/get/delete images using a RESTful HTTP API. There is also support for adding meta data to the images stored in Imbo. The main idea behind Imbo is to have a place to store high quality original images and to use the API to fetch variations of the images. Imbo will resize, rotate and crop (amongst other transformations) images on the fly so you won't have to store all the different variations.

Imbo is an open source project written in `PHP <http://php.net>`_ and is `available on GitHub <https://github.com/imbo/imbo>`_. If you find any issues or missing features please add an issue in the `issue tracker <https://github.com/imbo/imbo/issues>`_. If you want to know more feel free to join the #imbo channel on the `Freenode IRC network <http://freenode.net>`_ (chat.freenode.net) as well.

Installation guide
------------------
.. toctree::
    :maxdepth: 2

    installation/requirements
    installation/installation
    installation/configuration

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
    develop/cache_adapters
    develop/custom_database_adapters
    develop/custom_storage_adapters
    develop/image_transformations
    advanced/custom_event_listeners
