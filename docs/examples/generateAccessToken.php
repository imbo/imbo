<?php
$user       = "<user>";         // The user id
$publicKey  = "<public key>";   // The public key of the user
$privateKey = "<secret value>"; // The private key of the user
$image      = "<image>";        // The image identifier

// Image transformations
$query = [
    "t[]=thumbnail:width=40,height=40,fit=outbound",
    "t[]=border:width=3,height=3,color=000",
    "t[]=canvas:width=100,height=100,mode=center"
];

// Add a query parameter for public key if it differs from the user
if ($user != $publicKey) {
    $query[] = 'publicKey=' . $publicKey;
}

// The URI
$uri = sprintf("http://imbo/users/%s/images/%s?%s", $user, $image, implode('&', $query));

// Generate the token
$accessToken = hash_hmac("sha256", $uri, $privateKey);

// Output the URI with the access token
echo sprintf("%s&accessToken=%s", $uri, $accessToken) . PHP_EOL;
