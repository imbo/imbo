PHP Image Server
================
PHP Image Server (**PHPIMS**) is an image "server" that can be used to add/get/delete images using a REST interface. There is also support for adding metadata to an image. The main idea behind PHPIMS is to have a place to store high quality original images and to use the REST interface to fetch variations of those images. PHPIMS will resize, rotate, crop, switch formats (amonst other features) on the fly so you won't have to store all the different variations. PHPIMS comes with an administration dashboard that can be used to locate images. The dashboard will also support editing of metadata.

REST API
--------
PHPIMS uses a REST API to manage the images. Each image will be identified by an MD5 sum of the image itself and the original file extension, that will be referred to as &lt;image&gt; for the remainder of this document.

**GET /&lt;image&gt;**

Fetch the image identified by &lt;image&gt;. The following query parameters are supported:

* `(int) width` Width of the image in pixels.
* `(int) height` Height of the image in pixels.
* `(string) format` The file format (supported formats: jpg, gif and png).

If no options are specified the original image will be returned.

**GET /&lt;image&gt;/meta**

Get metadata related to the image identified by &lt;image&gt;. The metadata will be JSON encoded.

**POST /&lt;image&gt;**

Place a new image on the server along with metadata.

**POST /&lt;image&gt;/meta**

Edit the metadata attached to the image identified by &lt;image&gt;.

**DELETE /&lt;image&gt;**

Delete the image identified by &lt;image&gt; along with all metadata. This action is not reversable.

**DELETE /&lt;image&gt;/meta**

Delete the metadata attache to the image identified by &lt;image&gt;. The image is kept on the server. This action is not reversable.

**HEAD /&lt;image&gt;**

Fetches extra header information about a single image identified by &lt;image&gt;.

Extra response headers
-------------
PHPIMS will usually inject extra response headers to the different requests. All response headers from PHPIMS will be prefixed with **X-PHPIMS-**.

PHP client
----------
A PHP client is included in PHPIMS that supports all the REST methods and includes some convenience methods.

** Add an image **

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client();
    $client->setServerUrl('http://<hostname>');

    // Path to local image    
    $path = '/path/to/image.png';
    
    // Add some metadata to the image
    $metadata = array(
        'foo' => 'bar', 
        'bar' => 'foo',
    );
    
    // Make the request
    $response = $client->addImage($path, $metadata);
    
In the `$response` variable you will find the image hash that you will need to identify the added image in other operations. This hash is as mentioned above the MD5 sum of the file itself and the original file extension. 

    <?php
    print($response); // {"hash":"64223c5af0bfd3d90cf30af553ceea13"}
    
The response from the client is actually a `PHPIMS_Client_Response` object that holds all response headers and the body. When used in a string context it will return the body as JSON-encoded data. The response object has the following methods:

* `array` `getHeaders(void)`
* `array` `asArray(void)`
* `stdClass` `asObject(void)`
* `boolean` `isOk(void)`
* `int` `getStatusCode(void)`

** Get metadata **

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client();
    $client->setServerUrl('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->getImageMetadata($hash);

** Delete an image **

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client();
    $client->setServerUrl('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->deleteImage($hash);
    
** Delete all metadata attached to an image **

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client();
    $client->setServerUrl('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->deleteImageMetadata($hash);