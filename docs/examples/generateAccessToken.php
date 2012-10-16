<?php
$publicKey  = '<user>';         // The public key of the user
$privateKey = '<secret value>'; // The private key of the user
$image      = '<image>';        // The image identifier

// The URI
$url = sprintf('http://example.com/users/%s/images/%s', $publicKey, $image);

// Add some transformations
$transformations = array(
    't[]=thumbnail:width=40,height=40,fit=outbound',
    't[]=border:width=3,height=3,color=000',
    't[]=canvas:width=100,height=100,mode=center'
);
$query = implode('&', $transformations);

// Data for the HMAC
$url .= '?' . $query;

// Generate the token
$accessToken = hash_hmac('sha256', $url, $privateKey);

// Output the URI with the access token
echo $url . '&accessToken=' . $accessToken;
