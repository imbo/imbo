<?php
$publicKey  = "<user>";                 // The public key of the user
$privateKey = "<secret value>";         // The private key of the user
$timestamp  = gmdate("Y-m-d\TH:i:s\Z"); // Current timestamp (UTC)
$image      = "<image>";                // The image identifier
$method     = "DELETE";                 // HTTP method to use

// The URI
$uri = sprintf("http://imbo/users/%s/images/%s", $publicKey, $image);

// Data for the hash
$data = implode("|", array($method, $uri, $publicKey, $timestamp));

// Generate the token
$signature = hash_hmac("sha256", $data, $privateKey);

# Request the URI
$response = file_get_contents($uri, false, stream_context_create(array(
    "http" => array(
        "method" => $method,
        "header" => array(
            "X-Imbo-Authenticate-Signature: " . $signature,
            "X-Imbo-Authenticate-Timestamp: " . $timestamp,
        ),
    ),
)));
