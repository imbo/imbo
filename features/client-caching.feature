Feature: Imbo enables client caching using related response headers
    In order to be able to cache resources
    As an HTTP Client
    I can make look at the cache-related response headers set by Imbo

    Background:
        Given "tests/Fixtures/image1.png" exists for user "user"

    Scenario: Request index page (not cacheable)
        When I request "/"
        Then the response can not be cached
        And the "last-modified" response header does not exist
        And the "etag" response header does not exist

    Scenario: Request status information (not cacheable)
        When I request "/status"
        Then the response can not be cached
        And the "last-modified" response header does not exist
        And the "etag" response header does not exist

    Scenario: Request stats information (not cacheable)
        When I request "/stats?statsAllow=*"
        Then the response can not be cached
        And the "last-modified" response header does not exist
        And the "etag" response header does not exist

    Scenario: Request user information
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user"
        Then the response can be cached

    Scenario: Request user images
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user/images"
        Then the response can be cached
        And the response has a max-age of 0 seconds
        And the response has a "must-revalidate" cache-control directive

    Scenario: Request user images with custom caching configuration
        Given Imbo uses the "custom-http-cache.php" configuration
        And I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user/images"
        Then the response has a max-age of 15 seconds
        And the response does not have a "must-revalidate" cache-control directive

    Scenario: Request user image
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        And the "Accept" request header is "image/*"
        When I request the previously added image
        Then the response can be cached
        And the response has a max-age of 31536000 seconds

    Scenario: Request user image metadata
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request the metadata of the previously added image
        Then the response can be cached
