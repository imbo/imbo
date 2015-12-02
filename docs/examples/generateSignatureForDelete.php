<?php
$user       = "<user>";                 // User id
$publicKey  = "<public key>";           // The public key of the user
$privateKey = "<secret value>";         // The private key of the user
$timestamp  = gmdate("Y-m-d\TH:i:s\Z"); // Current timestamp (UTC)
$image      = "<image>";                // The image identifier
$method     = "DELETE";                 // HTTP method to use

// The URI
$uri = sprintf("http://imbo/users/%s/images/%s", $user, $image);

// Data for the hash
$data = implode("|", [$method, $uri, $publicKey, $timestamp]);

// Generate the token
$signature = hash_hmac("sha256", $data, $privateKey);

// Request the URI
$response = file_get_contents($uri, false, stream_context_create([
    "http" => [
        "method" => $method,
        "header" => [
            "X-Imbo-PublicKey " . $publicKey,
            "X-Imbo-Authenticate-Signature: " . $signature,
            "X-Imbo-Authenticate-Timestamp: " . $timestamp,
        ],
    ],
]));
