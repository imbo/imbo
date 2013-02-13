Feature: Imbo provides a user endpoint
    In order to see information about a user
    As an HTTP Client
    I want to make requests against the user endpoint

    Scenario: Request user information using a valid access token
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey"
        Then I should get a response with "200 OK"

    Scenario: Request user information using the wrong private key
        Given I use "publickey" and "foobar" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey"
        Then I should get a response with "400 Bad Request"
        And the Imbo error message is "Incorrect access token" and the error code is "0"

    Scenario: Request user information without a valid access token
        Given I use "publickey" and "foobar" for public and private keys
        When I request "/users/publickey"
        Then I should get a response with "400 Bad Request"
        And the Imbo error message is "Missing access token" and the error code is "0"

    Scenario: Request user that does not exist
        Given I use "foo" and "bar" for public and private keys
        When I request "/users/foo"
        Then I should get a response with "404 Not Found"
        And the Imbo error message is "Unknown Public Key" and the error code is "100"

    Scenario: Request user information using POST
        Given I use "publickey" and "privatekey" for public and private keys
        When I request "/users/publickey" using HTTP "POST"
        Then I should get a response with "405 Method Not Allowed"

    Scenario: Request user information using HEAD
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey" using HTTP "HEAD"
        Then I should get a response with "200 OK"
        And the response body should be empty
