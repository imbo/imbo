Feature: Imbo provides a user endpoint
    In order to see information about a user
    As an HTTP Client
    I want to make requests against the user endpoint

    Scenario: Request user information using a valid access token
        Given the user "username" exists with private key "key"
        And I include an access token in the query
        When I request "/users/username.json"
        Then I should get a response with "200" "OK"
        And the "Content-Type" response header is "application/json"


    Scenario: Request user information without a valid access token
        Given the user "username" exists with private key "key"
        When I request "/users/username.json"
        Then I should get a response with "400" "Bad Request"
        And the "Content-Type" response header is "application/json"
