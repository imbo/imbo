Feature: Imbo supports HTTP HEAD for all resources
    In order to fetch information about resources
    As an HTTP Client
    I can make requests using HTTP HEAD and get the same headers as if I did a GET

    Background:
        Given "tests/phpunit/Fixtures/image1.png" exists in Imbo

    Scenario: Request status information
        When I request "/status" using HTTP "HEAD"
        And make the same request using HTTP "GET"
        Then the following response headers should be the same:
        """
        cache-control
        allow
        vary
        content-type
        content-length
        """

    Scenario: Request user information
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/user" using HTTP "HEAD"
        And make the same request using HTTP "GET"
        Then the following response headers should be the same:
        """
        cache-control
        allow
        vary
        content-type
        content-length
        """

    Scenario: Request user images using a valid access token
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/user/images" using HTTP "HEAD"
        And make the same request using HTTP "GET"
        Then the following response headers should be the same:
        """
        cache-control
        allow
        vary
        content-type
        content-length
        """

    Scenario: Fetch image information
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "image/png"
        When I request the previously added image using HTTP "HEAD"
        And make the same request using HTTP "GET"
        Then the following response headers should be the same:
        """
        cache-control
        allow
        vary
        content-type
        content-length
        X-Imbo-Originalextension
        X-Imbo-Originalfilesize
        X-Imbo-Originalheight
        X-Imbo-Originalmimetype
        X-Imbo-Originalwidth
        """
