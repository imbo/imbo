<?php
$publicKey  = "<user>";         // The public key of the user
$privateKey = "<secret value>"; // The private key of the user
$image      = "<image>";        // The image identifier

// Image transformations
$query = implode("&", array(
    "t[]=thumbnail:width=40,height=40,fit=outbound",
    "t[]=border:width=3,height=3,color=000",
    "t[]=canvas:width=100,height=100,mode=center"
));

// The URI
$uri = sprintf("http://imbo/users/%s/images/%s?%s", $publicKey, $image, $query);

// Generate the token
$accessToken = hash_hmac("sha256", $uri, $privateKey);

// Output the URI with the access token
echo sprintf("%s&accessToken=%s", $uri, $accessToken);
