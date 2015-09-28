Feature: Imbo provides a user endpoint
    In order to see information about a user
    As an HTTP Client
    I want to make requests against the user endpoint

    Scenario Outline: Request user information
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/user.<extension>"
        Then I should get a response with "200 OK"
        And the response body matches:
           """
           <response>
           """

        Examples:
            | extension | response |
            | json      | #^{"user":"user","numImages":0,"lastModified":"[^"]+"}$# |
            | xml       | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<user>\s*<user>user</user>\s*<numImages>0</numImages>\s*<lastModified>[^<]+</lastModified>\s*</user>\s*</imbo>$#ms |

    Scenario: Request user that does not exist
        Given I use "foo" and "bar" for public and private keys
        When I request "/users/foo.json"
        Then I should get a response with "400 Permission denied (public key)"
        And the Imbo error message is "Permission denied (public key)" and the error code is "0"

    Scenario Outline: The user endpoint only supports HTTP GET and HEAD
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/user.json" using HTTP "<method>"
        Then I should get a response with "<status>"

        Examples:
            | method | status                 |
            | GET    | 200 OK                 |
            | HEAD   | 200 OK                 |
            | POST   | 405 Method not allowed |
            | PUT    | 405 Method not allowed |
            | DELETE | 405 Method not allowed |
            | SEARCH | 405 Method not allowed |
