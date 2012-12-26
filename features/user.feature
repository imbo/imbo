Feature: Imbo provides a user endpoint
    In order to see information about a user
    As an HTTP Client
    I want to make requests against the user endpoint

    Scenario: Request user information using a valid access token
        Given the user "username" exists with private key "key"
        And I include an access token in the query
        When I request "/users/username.json"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"

    Scenario: Request user information without a valid access token
        Given the user "username" exists with private key "key"
        When I request "/users/username.json"
        Then I should get a response with "400 Bad Request"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Missing access token" and the error code is "0"

    Scenario: Request user that does not exist
        When I request "/users/usernamedoesnotexist.json"
        Then I should get a response with "404 Not Found"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Unknown Public Key" and the error code is "100"

    Scenario: Request user that does not exist
        When I request "/users/usernamedoesnotexist.xml"
        Then I should get a response with "404 Not Found"
        And the "Content-Type" response header is "application/xml"
        And the Imbo error message is "Unknown Public Key" and the error code is "100"
