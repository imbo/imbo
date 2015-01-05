Feature: Imbo requires write operations to be signed
    In order to sign write operations
    As an HTTP Client
    I can specify a signature and timestamp as request headers or as query parameters

    Scenario: Authenticate using request headers
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request using HTTP headers
        And I attach "tests/phpunit/Fixtures/image1.png" to the request body
        When I request "/users/publickey/images" using HTTP "POST"
        Then I should get a response with "201 Created"

    Scenario: Authenticate using query parameters
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2" using HTTP "DELETE"
        Then I should get a response with "200 OK"

    Scenario: Add an image with no authentication information
        Given I use "publickey" and "privatekey" for public and private keys
        And I attach "tests/phpunit/Fixtures/image1.png" to the request body
        When I request "/users/publickey/images" using HTTP "POST"
        Then I should get a response with "400 Missing authentication timestamp"
        And the Imbo error message is "Missing authentication timestamp" and the error code is "101"

    Scenario: Add an image with an invalid timestamp
        Given I use "publickey" and "privatekey" for public and private keys
        And the "X-Imbo-Authenticate-Timestamp" request header is "foobar"
        And I attach "tests/phpunit/Fixtures/image1.png" to the request body
        When I request "/users/publickey/images" using HTTP "POST"
        Then I should get a response with "400 Invalid timestamp: foobar"
        And the Imbo error message is "Invalid timestamp: foobar" and the error code is "102"

    Scenario: Add an image with an expired timestamp
        Given I use "publickey" and "privatekey" for public and private keys
        And the "X-Imbo-Authenticate-Timestamp" request header is "2010-02-03T01:02:03Z"
        And I attach "tests/phpunit/Fixtures/image1.png" to the request body
        When I request "/users/publickey/images" using HTTP "POST"
        Then I should get a response with "400 Timestamp has expired: 2010-02-03T01:02:03Z"
        And the Imbo error message is "Timestamp has expired: 2010-02-03T01:02:03Z" and the error code is "104"

    Scenario: Add an image with a missing signature
        Given I use "publickey" and "privatekey" for public and private keys
        And the "X-Imbo-Authenticate-Timestamp" request header is "current-timestamp"
        And I attach "tests/phpunit/Fixtures/image1.png" to the request body
        When I request "/users/publickey/images" using HTTP "POST"
        Then I should get a response with "400 Missing authentication signature"
        And the Imbo error message is "Missing authentication signature" and the error code is "101"

    Scenario: Add an image with an incorrect signature
        Given I use "publickey" and "privatekey" for public and private keys
        And the "X-Imbo-Authenticate-Timestamp" request header is "current-timestamp"
        And the "X-Imbo-Authenticate-Signature" request header is "foobar"
        And I attach "tests/phpunit/Fixtures/image1.png" to the request body
        When I request "/users/publickey/images" using HTTP "POST"
        Then I should get a response with "400 Signature mismatch"
        And the Imbo error message is "Signature mismatch" and the error code is "103"

    Scenario: Authenticate using a read-only private key
        Given I use "publickey" and "read-only-key" for public and private keys
        And I sign the request using HTTP headers
        And I attach "tests/phpunit/Fixtures/image1.png" to the request body
        And Imbo uses the "ro-rw-auth.php" configuration
        When I request "/users/publickey/images" using HTTP "POST"
        Then I should get a response with "400 Signature mismatch"
        And the Imbo error message is "Signature mismatch" and the error code is "103"

    Scenario: Authenticate using a read+write private key
        Given I use "rw-pubkey" and "read+write-key" for public and private keys
        And I sign the request using HTTP headers
        And I attach "tests/phpunit/Fixtures/image1.png" to the request body
        And Imbo uses the "ro-rw-auth.php" configuration
        When I request "/users/someuser/images" using HTTP "POST"
        Then I should get a response with "201 Created"
