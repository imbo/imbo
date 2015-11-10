Feature: Imbo enables client caching using related response headers
    In order to be able to cache resources
    As an HTTP Client
    I can make look at the cache-related response headers set by Imbo

    Background:
        Given "tests/phpunit/Fixtures/image1.png" exists in Imbo

    Scenario: Request index page (not cacheable)
        When I request "/"
        Then the response is not cacheable
        And the following response headers should not be present:
        """
        last-modified
        etag
        """

    Scenario: Request status information (not cacheable)
        When I request "/status"
        Then the response is not cacheable
        And the following response headers should not be present:
        """
        last-modified
        etag
        """

    Scenario: Request stats information (not cacheable)
        When I request "/stats=statsAllow=*"
        Then the response is not cacheable
        And the following response headers should not be present:
        """
        last-modified
        etag
        """

    Scenario: Request user information
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/user"
        Then the response is cacheable

    Scenario: Request user images
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/user/images"
        Then the response is cacheable
        And the response has a max age of 0 seconds
        And the response has a must-revalidate directive

    Scenario: Request user images with custom caching configuration
        Given Imbo uses the "custom-http-cache.php" configuration
        And I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/user/images"
        Then the response has a max age of 15 seconds
        And the response does not have a must-revalidate directive

    Scenario: Request user image
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "image/*"
        When I request the previously added image
        Then the response is cacheable
        And the response has a max age of 31536000 seconds

    Scenario: Request user image metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request the metadata of the previously added image
        Then the response is cacheable
