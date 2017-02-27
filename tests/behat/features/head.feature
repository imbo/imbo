Feature: Imbo supports HTTP HEAD for all resources
    In order to fetch information about resources
    As an HTTP Client
    I can make requests using HTTP HEAD and get the same headers as if I did a GET

    Background:
        Given "tests/phpunit/Fixtures/image1.png" exists for user "user"

    Scenario: Request status information
        When I request "/status" using HTTP "HEAD"
        And I replay the last request using HTTP "GET"
        Then the following response headers should be the same:
            """
            cache-control
            allow
            vary
            content-type
            content-length
            """

    Scenario: Request user information
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user" using HTTP "HEAD"
        And I replay the last request using HTTP "GET"
        Then the following response headers should be the same:
            """
            cache-control
            allow
            vary
            content-type
            content-length
            """

    Scenario: Request user images using a valid access token
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user/images" using HTTP "HEAD"
        And I replay the last request using HTTP "GET"
        Then the following response headers should be the same:
            """
            cache-control
            allow
            vary
            content-type
            content-length
            """

    Scenario: Fetch image information
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        And the "Accept" request header is "image/png"
        When I request the previously added image using HTTP "HEAD"
        And I replay the last request using HTTP "GET"
        Then the following response headers should be the same:
            """
            cache-control
            allow
            vary
            content-type
            content-length
            X-imbo-originalextension
            X-imbo-originalfilesize
            X-imbo-originalheight
            X-imbo-originalmimetype
            X-imbo-originalwidth
            """
