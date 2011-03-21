#PHP Image Server
PHP Image Server (**PHPIMS**) is an image "server" that can be used to add/get/delete images using a REST interface. There is also support for adding metadata to an image. The main idea behind PHPIMS is to have a place to store high quality original images and to use the REST interface to fetch variations of those images. PHPIMS will resize, rotate, crop, switch formats (amonst other features) on the fly so you won't have to store all the different variations. PHPIMS comes with an administration dashboard that can be used to locate images. The dashboard will also support editing of metadata.

##REST API
PHPIMS uses a REST API to manage the images. Each image will be identified by an MD5 sum of the image itself and the original file extension, that will be referred to as &lt;image&gt; for the remainder of this document.

###GET /&lt;image&gt;

Fetch the image identified by &lt;image&gt;. The following query parameters are supported:

* `(int) width` Width of the image in pixels.
* `(int) height` Height of the image in pixels.

If no options are specified the original image will be returned.

###GET /&lt;image&gt;/meta

Get metadata related to the image identified by &lt;image&gt;. The metadata will be JSON encoded.

###POST /&lt;image&gt;

Place a new image on the server along with metadata.

###POST /&lt;image&gt;/meta

Edit the metadata attached to the image identified by &lt;image&gt;.

###DELETE /&lt;image&gt;

Delete the image identified by &lt;image&gt; along with all metadata. This action is not reversable.

###DELETE /&lt;image&gt;/meta

Delete the metadata attache to the image identified by &lt;image&gt;. The image is kept on the server. This action is not reversable.

###HEAD /&lt;image&gt;

Fetches extra header information about a single image identified by &lt;image&gt;.

###HEAD /&lt;image&gt;/meta

Fetches extra header information about the metadata attached to the image identified by &lt;image&gt;. 

##Extra response headers
PHPIMS will usually inject extra response headers to the different requests. All response headers from PHPIMS will be prefixed with **X-PHPIMS-**.

##PHP client
A PHP client is included in PHPIMS that supports all the REST methods and includes some convenience methods.

###Add an image

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
    
    $response = $client->addImage($path, $metadata);
    
###Get metadata

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client();
    $client->setServerUrl('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->getMetadata($hash);

###Delete an image

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client();
    $client->setServerUrl('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->deleteImage($hash);
    
###Delete all metadata attached to an image

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client();
    $client->setServerUrl('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->deleteMetadata($hash);

###Client response object    
All client methods returns an instance of `PHPIMS_Client_Response`. In this instance you will find information on the request that was made. 

The response instance includes all response headers and the body, and has the following methods:

* `array` `getHeaders(void)`: Get all response headers 
* `array` `asArray(void)`: Get the body as a native PHP array instead of a JSON-encoded string
* `stdClass` `asObject(void)`: Get the body as an instance of stdClass instead of a JSON-encoded string
* `boolean` `isSuccess(void)`: Wether or not the response was a success (true if the HTTP status code is in the 2xx range) 
* `int` `getStatusCode(void)`: Get the status code