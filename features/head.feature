Feature: Imbo supports HTTP HEAD for all resources
    In order to fetch information about resources
    As an HTTP Client
    I can make requests using HTTP HEAD and get the same headers as if I did a GET

    Background:
        Given "tests/Fixtures/image1.png" exists for user "user"

    Scenario: Request status information
        When I request "/status" using HTTP "HEAD"
        And I replay the last request using HTTP "GET"
        Then the last 2 "cache-control" response headers are the same
        Then the last 2 "allow" response headers are the same
        Then the last 2 "vary" response headers are the same
        Then the last 2 "content-type" response headers are the same
        Then the last 2 "content-length" response headers are the same

    Scenario: Request user information
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user" using HTTP "HEAD"
        And I replay the last request using HTTP "GET"
        Then the last 2 "cache-control" response headers are the same
        Then the last 2 "allow" response headers are the same
        Then the last 2 "vary" response headers are the same
        Then the last 2 "content-type" response headers are the same
        Then the last 2 "content-length" response headers are the same

    Scenario: Request user images using a valid access token
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user/images" using HTTP "HEAD"
        And I replay the last request using HTTP "GET"
        Then the last 2 "cache-control" response headers are the same
        Then the last 2 "allow" response headers are the same
        Then the last 2 "vary" response headers are the same
        Then the last 2 "content-type" response headers are the same
        Then the last 2 "content-length" response headers are the same

    Scenario: Fetch image information
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        And the "Accept" request header is "image/png"
        When I request the previously added image using HTTP "HEAD"
        And I replay the last request using HTTP "GET"
        Then the last 2 "cache-control" response headers are the same
        Then the last 2 "allow" response headers are the same
        Then the last 2 "vary" response headers are the same
        Then the last 2 "content-type" response headers are the same
        Then the last 2 "content-length" response headers are the same
        Then the last 2 "X-imbo-originalextension" response headers are the same
        Then the last 2 "X-imbo-originalfilesize" response headers are the same
        Then the last 2 "X-imbo-originalheight" response headers are the same
        Then the last 2 "X-imbo-originalmimetype" response headers are the same
        Then the last 2 "X-imbo-originalwidth" response headers are the same
