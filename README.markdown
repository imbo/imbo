PHP Image Server
================
PHP Image Server (**PHPIMS**) is an image "server" that can be used to add/get/delete images using a REST interface. There is also support for adding metadata to an image. The main idea behind PHPIMS is to have a place to store high quality original images and to use the REST interface to fetch variations of those images. PHPIMS will resize, rotate, crop, switch formats (amonst other features) on the fly so you won't have to store all the different variations. PHPIMS comes with an administration dashboard that can be used to locate images. The dashboard will also support editing of metadata.

REST API
--------
PHPIMS uses a REST API to manage the images. Each image will be identified by a unique hash that will be referred to as &lt;hash&gt; for the remainder of this document. The hash will be generated on the image server when an image is added and will look like:

([a-f0-9]/){3}[a-f0-9]{32} where the first three letters is the first three letters of the hash. For instance:

1/a/f/1af52abe5101aa01db6544ad28db5a16 

**GET /images/&lt;hash&gt;**

Fetch the image identified by &lt;hash&gt;. The following options are supported:
* (int) width Width of the image in pixels
* (int) height Height of the image in pixels
* (string) format The file format (supported formats: jpg, gif and png)
* (int) quality The quality of the resulting image (0-100 where 100 is the best quality. Not all image formats supports this)

If no options are specified the original image will be returned.

**GET /images/&lt;hash&gt;/meta**

Get metadata related to the image identified by &lt;hash&gt;. The metadata will be JSON encoded.

**DELETE /images/&lt;hash&gt;**

Delete the image identified by &lt;hash&gt;.

**POST /[&lt;hash&gt;]**

Place a new image on the server along with metadata. Can be used to manipulate metadata when used with a hash. If

**HEAD /[images/&lt;hash&gt;]**

Fetches extra header information about a single image or about the site in general when used without the &lt;hash&gt;.

Extra headers
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
    $image = '/path/to/image.png';
    
    // Add some metadata to the image
    $metadata = array(
        'foo' => 'bar', 
        'bar' => 'foo',
    );
    
    // Make the request
    $response = $client->add($image, $metadata);
    
In the `$response` variable you will find the image hash that you will need to identify the added image in other operations.

    <?php
    print($response); // {"id":"64223c5af0bfd3d90cf30af553ceea13"}
    
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
    $response = $client->getMetadata($hash);

** Delete an image **

    <?php
    require 'PHPIMS/Autoload.php';

    $client = new PHPIMS_Client();
    $client->setServerUrl('http://<hostname>');
    
    $hash = '<hash>';
    $response = $client->deleteImage($hash);
    
This will permanently remove the image from the image server.