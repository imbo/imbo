Changelog for Imbo
==================

Imbo-1.2.0
----------
__N/A__

* #269: Return metadata on write requests against the metadata resource
* #253: Store the original checksum of added images

Bug fixes:

* #270: Access-Control-Allow-Origin header not included when CORS is enabled and Imbo generates an error

Imbo-1.1.1
----------
__2014-02-14__

Bug fixes:

* #258: In special cases, images can be accessed without access tokens

Imbo-1.1.0
----------
__2014-02-13__

* #256: Improved crop functionality
* #255: Index resource is no longer cache-able
* #254: Improved handling of the ETag response header
* #252: New image transformation: modulate
* #250: The Varnish HashTwo event listener sends multiple headers

Imbo-1.0.2
----------
__2014-02-14__

Bug fixes:

* #258: In special cases, images can be accessed without access tokens

Imbo-1.0.1
----------
__2014-02-06__

Bug fixes:

* #251: Stats access event listener is triggered after the stats have been generated
* #249: Stats access event listener does not authenticate HTTP HEAD requests

Imbo-1.0.0
----------
__2014-01-30__

* #248: Changed format of the parameters for event listeners
* #238: Added ids[] and checksums[] as filters for the images resource
* #234: Added pagination info to the images resource
* #232: New image transformation: strip
* #231: New image transformation: progressive
* #228: Added support for wildcards in the ExifMetadata listener
* #218: Support custom parameters when triggering events
* #210: Image transformations must implement the image transformation interface
* #205: Added a Varnish HashTwo event listener
* #204: Images resource should be able to retrieve only specific fields
* #201: Added an index resource
* #199: Allow /metadata for the meta data resource
* #196: Added Amazon Simple Storage Service (S3) storage adapter
* #192: Images resource should allow sorting
* #190: Added support for filtering images on one or more image identifiers
* #189: Prioritize original image mime types when doing content negotiation
* #188: Support an alternative installation method
* #186: Added support for short image URL's
* #185: Add support for custom resources and routes
* #174: New image transformation: watermark
* #161: Images can now only be added by requesting the images resource using HTTP POST

Bug fixes:

* #237: Fixed possible PHP Warnings when the transformation query parameter is invalid
* #222: Some images are not correctly identified
* #220: Incorrect IP matching in the stats access event listener
* #211: CORS event listener suppresses "405 Method not allowed" responses when enabled
* #103: Compress transformation only works as expected with image/jpeg

Imbo-0.3.3
----------
__2013-10-19__

* Fixed #214: 0.3.2 is not installable without updating dependencies

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
