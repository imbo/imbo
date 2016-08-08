Changelog for Imbo
==================

Imbo-2.2.0
----------
__2016-08-08__

* #475: Validate signed URLs regardless of t[] vs t[0] (Mats Lindh)
* #459: Support wildcards when subscribing to events (Christer Edvartsen)
* #444: Added a getData() method to the Imbo\Model\ModelInterface (Christer Edvartsen)
* #431: Added an Amazon S3 storage adapter for the image variations (Ali Asaria)

Bug fixes:

* #463: Fixed issue with an error model being formatted as an image (Christer Edvartsen)
* #462: Adding short URLs to an image that does not exist now results in 404 (Christer Edvartsen)

Imbo-2.1.3
----------
__2016-05-05__

* #469: Move the mongodb/mongodb dependency back to require as it was moved to require-dev for 2.1.2 (Christer Edvartsen)

Imbo-2.1.2
----------
__2016-05-04__

* #467: Use finfo to determine mime type of uploaded images before loading them into ImageMagick (Christer Edvartsen)

Imbo-2.1.1
----------
__2016-03-11__

* #437: Fixed issue with metadata not being formatted correctly using the new Mongo adapters (Christer Edvartsen)

Imbo-2.1.0
----------
__2016-03-10__

* #436: Added new MongoDB adapters that use the mongodb extension (Christer Edvartsen)
* #434: Custom models for group(s) and access rule(s) (Christer Edvartsen)

Imbo-2.0.0
----------
__2016-02-28__

* #429: Added opacity to watermark image (Sindre Gulseth)
* #427: When resizing with one param round up the other calculated value (Sindre Gulseth)
* #423: Make storage drivers throw exceptions as expected in StorageOperations (Mats Lindh)
* #410: Validate incoming image metadata (Kristoffer Brabrand)
* #402: Improve Contrast-transformation predictability (Espen Hovlandsdal)
* #400: New image transformation: Blur (Kristoffer Brabrand)
* #398: Add ability to configure HTTP cache headers (Espen Hovlandsdal)
* #391: Make Crop-transformation validate coordinates (Espen Hovlandsdal)
* #381: New image transformation: DrawPois (points of interest) (Espen Hovlandsdal)
* #376: Add config option to alter protocol used for authentication signatures (Espen Hovlandsdal)
* #363: Add pluggable image identifier generation (Espen Hovlandsdal)
* #357: Add public key generation CLI-command (Espen Hovlandsdal)
* #351: New image transformation: SmartSize (Kristoffer Brabrand, Espen Hovlandsdal)
* #348: Add global images endpoint (Kristoffer Brabrand)
* #347: Use UUID instead of MD5 for image identifiers (Espen Hovlandsdal)
* #328: New access control implementation (Espen Hovlandsdal, Kristoffer Brabrand)

Bug fixes:

* #430: Prevent race conditions in image transformation cache (Espen Hovlandsdal)
* #402: Fix Strip-transformation not doing anything on newer Imagick versions (Espen Hovlandsdal)
* #390: Fix image variation + crop transformation bug (Espen Hovlandsdal)
* #386: Fix CORS wildcard-issue when client did not send `Origin`-header (Espen Hovlandsdal)
* #367: Fix bug where special characters could break metadata XML response (Kristoffer Brabrand)
* #366: Fix border transformation + alpha channel bug (Espen Hovlandsdal)

Imbo-1.2.5
----------
__2015-08-14__

* #342: Generate private keys with base64-encoded raw random bytes (Mats Lindh)
* #341: Update documentation regarding private key generation script (Mats Lindh)
* #340: Add "region" parameter to S3 storage adapter (chrisitananese)
* #339: Vary on Origin when responding to OPTIONS CORS-requests (Morten Fangel)
* #336: Fix small typo for autoload locations (Christian Foster)
* #333: Fix usage of hard coded temp directory in FileSystem-based tests (Mats Lindh)
* #332: Fix StripImageTest under Windows (Mats Lindh)
* #331: Fix GeneratePrivateKeyTest and FileSystemTest under Windows (Mats Lindh)
* #329: Add event hooks to accessToken and authenticate event listeners (Kristoffer Brabrand)
* #327: Add support for SEARCH verb to router (Kristoffer Brabrand)
* #320: Allow setting public key in headers and/or query (Espen Hovlandsdal)
* #321: Behat config option for path of internal httpd log location (Espen Hovlandsdal)

Imbo-1.2.4
----------
__2014-12-04__

* #202: Added an event listener for generating multiple different image variations on upload, speeding up scaling transformations by loading the image variation closest in dimensions before scaling (Christer Edvartsen, Espen Hovlandsdal)

Bug fixes:

* #313: Doctrine database driver would throw an exception when it could not connect, making the status endpoint return bogus information (Morten Fangel)

Imbo-1.2.3
----------
__2014-11-11__

Bug fixes:

* #311: Doctrine database driver would not respect public key when listing images (Espen Hovlandsdal)

Imbo-1.2.2
----------
__2014-11-10__

* #310: Added `trustedProxies` configuration option (Espen Hovlandsdal)
* #307: Added ability to have multiple private keys per user + RO/RW access level (Espen Hovlandsdal)
* #289: Added support for alternative ways of specifying public / private keys (Christer Edvartsen)
* #305: Added [MongoFill](https://github.com/mongofill/mongofill) compatiblity (Espen Hovlandsdal)
* #300: Added configuration option to enable/disable content negotiation for images (`contentNegotiateImages`). Will behave as earlier versions by default (Espen Hovlandsdal)
* #308: New image transformation: vignette (Espen Hovlandsdal)
* #265: New image transformation: sharpen (Christer Edvartsen)
* #264: New image transformation: contrast (Christer Edvartsen)

Bug fixes:

* #298: Browsers could not send `X-Imbo-*`-headers across origins (Espen Hovlandsdal)
* #298: CORS-requests would not have the `Access-Control-Allow-Origin`-header present in the response if the request was incorrectly signed or missing the accessToken (Espen Hovlandsdal)

Imbo-1.2.1
----------
__2014-08-01__

* #295: Added PHP-5.6 to build matrix at Travis-CI (Espen Hovlandsdal)
* #293: Improved documentation (Mats Lindh)
* #291: Fixed issue with cli-tool not being able to find the autoloader

Bug fixes:

* #294: ShortUrls cannot be retrieved with latest MongoDB driver (Espen Hovlandsdal)
* #290: Vary on Origin when Cors-listener is enabled (Espen Hovlandsdal)

Imbo-1.2.0
----------
__2014-04-08__

* #286: Store images with mime types image/x-jpeg, image/x-png and image/x-gif as image/jpeg, image/png and image/gif respectively
* #285: New image transformation: histogram (Mats Lindh)
* #282: Added robots.txt and humans.txt
* #276: Support checking if the `accessToken` matches the URI "as is" (Peter Rudolfsen)
* #269: Return metadata on write requests against the metadata resource
* #260: Generate short URLs on demand
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
* #186: Added support for short image URLs
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
