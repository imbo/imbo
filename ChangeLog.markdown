Changelog for Imbo
=====================

Imbo-0.4.0
----------
__N/A__

* If clients have no preferences regarding images the original mime type of each image is prioritized
* Added support for custom routes and resources through the constructor
* Added watermark transformation

Imbo-0.3.2
----------
__2013-06-06__

* Fixed #182: Sometimes wrong content-type when errors occur

Imbo-0.3.1
----------
__2013-06-06__

* Updated composer dependencies

Imbo-0.3.0
----------
__2013-06-06__

* Improved image validation when adding images (Espen Hovlandsdal)
* Imbo no longer includes the HTML formatter
* Imbo no longer supports php < 5.4
* Imbo now uses the Symfony HttpFoundation Component
* Added support for signing requests using request headers
* Imbo can populate metadata from EXIF-tags on new images by enabling the ExifMetadata event listener (Espen Hovlandsdal)

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
