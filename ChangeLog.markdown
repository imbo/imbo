Changelog for Imbo
=====================

Imbo-0.2.1
----------
__2013-04-05__

* Fixed version number in end-user docs

Imbo-0.2.0
----------
__2013-03-31__

* Bumped the requirement of pecl/mongo to 1.3.0 because Imbo now uses the MongoClient class instead of the Mongo class
* The image transforation cache now also caches image formatting, and not only transformations triggered by query parameters
* Pull request #162: Imbo can auto-rotate new images by enabling the AutoRotateImage event listener (Kristoffer Brabrand)
* Fixed #156: Requests with XSS injections can break access token validation (André Roaldseth)
* The convert transformation can no longer be triggered via the t query parameter per default
* Fixed #150: Error model can't be formatted using an image formatter
* Fixed #149: Add mode to the border transformation

Imbo-0.1.0
----------
__2012-12-30__

* Initial release
