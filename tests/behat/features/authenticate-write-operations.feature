Feature: Imbo requires write operations to be signed
    In order to sign write operations
    As an HTTP Client
    I can specify a signature and timestamp as request headers or as query parameters

    Scenario: Authenticate using request headers
        Given I use "publicKey" and "privateKey" for public and private keys
        And I sign the request using HTTP headers
        And the request body contains "tests/phpunit/Fixtures/image1.png"
        When I request "/users/user/images" using HTTP "POST"
        Then the response status line is "201 Created"
        And the response body contains JSON:
            """
            {
              "imageIdentifier": "@regExp(/[a-z0-9]+/i)",
              "width": 599,
              "height": 417,
              "extension": "png"
            }
            """

    Scenario: Authenticate using query parameters
        Given "tests/phpunit/Fixtures/image.png" exists for user "user"
        And I use "publicKey" and "privateKey" for public and private keys
        And I sign the request
        When I request the previously added image using HTTP "DELETE"
        Then the response status line is "200 OK"
        And the response body contains JSON:
            """
            {"imageIdentifier": "@regExp(/[a-z0-9]+/i)"}
            """

    Scenario: Add an image with no authentication information
        Given I use "publicKey" and "privateKey" for public and private keys
        And the request body contains "tests/phpunit/Fixtures/image1.png"
        When I request "/users/user/images" using HTTP "POST"
        Then the response status line is "400 Missing authentication timestamp"
        And the Imbo error message is "Missing authentication timestamp" and the error code is "101"

    Scenario: Add an image with an invalid timestamp
        Given I use "publicKey" and "privateKey" for public and private keys
        And the "X-Imbo-Authenticate-Timestamp" request header is "foobar"
        And the request body contains "tests/phpunit/Fixtures/image1.png"
        When I request "/users/user/images" using HTTP "POST"
        Then the response status line is "400 Invalid timestamp: foobar"
        And the Imbo error message is "Invalid timestamp: foobar" and the error code is "102"

    Scenario: Add an image with an expired timestamp
        Given I use "publicKey" and "privateKey" for public and private keys
        And the "X-Imbo-Authenticate-Timestamp" request header is "2010-02-03T01:02:03Z"
        And the request body contains "tests/phpunit/Fixtures/image1.png"
        When I request "/users/user/images" using HTTP "POST"
        Then the response status line is "400 Timestamp has expired: 2010-02-03T01:02:03Z"
        And the Imbo error message is "Timestamp has expired: 2010-02-03T01:02:03Z" and the error code is "104"

    Scenario: Add an image with a missing signature
        Given I use "publicKey" and "privateKey" for public and private keys
        And the "X-Imbo-Authenticate-Timestamp" request header is "current-timestamp"
        And the request body contains "tests/phpunit/Fixtures/image1.png"
        When I request "/users/user/images" using HTTP "POST"
        Then the response status line is "400 Missing authentication signature"
        And the Imbo error message is "Missing authentication signature" and the error code is "101"

    Scenario: Add an image with an incorrect signature
        Given I use "publicKey" and "privateKey" for public and private keys
        And the "X-Imbo-Authenticate-Timestamp" request header is "current-timestamp"
        And the "X-Imbo-Authenticate-Signature" request header is "foobar"
        And the request body contains "tests/phpunit/Fixtures/image1.png"
        When I request "/users/user/images" using HTTP "POST"
        Then the response status line is "400 Signature mismatch"
        And the Imbo error message is "Signature mismatch" and the error code is "103"

    Scenario: Authenticate a write operation with a read-only private key
        Given Imbo uses the "ro-rw-auth.php" configuration
        And I use "ro-pubkey" and "read-only-key" for public and private keys
        And I sign the request using HTTP headers
        And the request body contains "tests/phpunit/Fixtures/image1.png"
        When I request "/users/someuser/images" using HTTP "POST"
        Then the response status line is "400 Permission denied (public key)"
        And the Imbo error message is "Permission denied (public key)" and the error code is "0"

    Scenario: Authenticate using a read+write private key
        Given Imbo uses the "ro-rw-auth.php" configuration
        And I use "rw-pubkey" and "read+write-key" for public and private keys
        And I sign the request using HTTP headers
        And the request body contains "tests/phpunit/Fixtures/image1.png"
        When I request "/users/someuser/images" using HTTP "POST"
        Then the response status line is "201 Created"
        And the response body contains JSON:
            """
            {
              "imageIdentifier": "@regExp(/[a-z0-9]+/i)",
              "width": 599,
              "height": 417,
              "extension": "png"
            }
            """
