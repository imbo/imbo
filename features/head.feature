Feature: Imbo supports HTTP HEAD for all resources
    In order to fetch information about resources
    As an HTTP Client
    I can make requests using HTTP HEAD and get the same headers as if I did a GET

    Scenario: Request user information using a valid access token
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/status" using HTTP "HEAD"
        And make the same request using HTTP "GET"
        Then the response headers should be the same
