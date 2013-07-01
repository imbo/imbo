Feature: Imbo requires an access token for read operations
    In order to get content from Imbo
    As an HTTP Client
    I must specify an access token in the URI

    Scenario: Request user information using the correct private key
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey"
        Then I should get a response with "200 OK"

    Scenario: Request user information using the wrong private key
        Given I use "publickey" and "foobar" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey"
        Then I should get a response with "400 Incorrect access token"
        And the Imbo error message is "Incorrect access token" and the error code is "0"

    Scenario: Request user information without a valid access token
        Given I use "publickey" and "foobar" for public and private keys
        When I request "/users/publickey"
        Then I should get a response with "400 Missing access token"
        And the Imbo error message is "Missing access token" and the error code is "0"
