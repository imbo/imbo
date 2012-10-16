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

// Output the URI with the signature and the timestamp
echo sprintf('%s?signature=%s&timestamp=%s',
             $url,
             rawurlencode($signature),
             rawurlencode($timestamp));
