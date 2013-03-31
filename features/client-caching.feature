Feature: Imbo enables client caching using related response headers
    In order to be able to cache resources
    As an HTTP Client
    I can make look at the cache-related response headers set by Imbo

    Scenario: Request status information (not cacheable)
        When I request "/status"
        Then the response is not cacheable
        And the following response headers should not be present:
        """
        last-modified
        etag
        """

    Scenario: Request user information
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey"
        Then the response is cacheable

    Scenario: Request user images
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images"
        Then the response is cacheable
