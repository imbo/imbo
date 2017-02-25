Feature: Imbo provides a user endpoint
    In order to see information about a user
    As an HTTP Client
    I want to make requests against the user endpoint

    Scenario: Request user information
        Given I include an access token in the query using "publickey" and "privatekey"
        When I request "/users/user.json"
        Then the response status line is "200 OK"
        And the response body contains JSON:
            """
            {
              "user": "user",
              "numImages": 0
            }
            """

    Scenario: Request user that does not exist
        Given I include an access token in the query using "publickey" and "privatekey"
        When I request "/users/foobar.json"
        Then the response status line is "400 Permission denied (public key)"
        And the Imbo error message is "Permission denied (public key)" and the error code is "0"

    Scenario Outline: The user endpoint only supports HTTP GET and HEAD
        Given I include an access token in the query using "publickey" and "privatekey"
        When I request "/users/user.json" using HTTP "<method>"
        Then the response status line is "<status>"

        Examples:
            | method | status                 |
            | GET    | 200 OK                 |
            | HEAD   | 200 OK                 |
            | POST   | 405 Method not allowed |
            | PUT    | 405 Method not allowed |
            | DELETE | 405 Method not allowed |
