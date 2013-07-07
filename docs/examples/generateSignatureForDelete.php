<?php
$publicKey  = '<user>';                 // The public key of the user
$privateKey = '<secret value>';         // The private key of the user
$timestamp  = gmdate('Y-m-d\TH:i:s\Z'); // Current timestamp
$image      = '<image>';                // The image identifier

// The URI
$url = sprintf('http://example.com/users/%s/images/%s', $publicKey, $image);

// The method to request with
$method = 'DELETE';

// Data for the hash
$data = $method . '|' . $url . '|' . $publicKey . '|' . $timestamp;

// Generate the token
$signature = hash_hmac('sha256', $data, $privateKey);

// Create the context for the request
$context = stream_context_create(array(
    'http' => array(
        'method' => $method,
        'header' => array(
            'X-Imbo-Authenticate-Signature: ' . $signature,
            'X-Imbo-Authenticate-Timestamp: ' . $timestamp,

        ),
    ),
));
file_get_contents($url, false, $context);
