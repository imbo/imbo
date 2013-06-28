Feature: Imbo provides a user endpoint
    In order to see information about a user
    As an HTTP Client
    I want to make requests against the user endpoint

    Scenario: Request user information represented as JSON
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey.json"
        Then I should get a response with "200 OK"
        And the response body matches:
           """
           #^{"publicKey":"publickey","numImages":0,"lastModified":"[^"]+"}$#
           """

    Scenario: Request user information represented as XML
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey.xml"
        Then I should get a response with "200 OK"
        And the response body matches:
           """
           #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<user>\s*<publicKey>publickey</publicKey>\s*<numImages>0</numImages>\s*<lastModified>[^<]+</lastModified>\s*</user>\s*</imbo>$#ms
           """

    Scenario: Request user that does not exist
        Given I use "foo" and "bar" for public and private keys
        When I request "/users/foo.json"
        Then I should get a response with "404 Unknown public key"
        And the Imbo error message is "Unknown public key" and the error code is "100"

    Scenario: Request user information using POST
        Given I use "publickey" and "privatekey" for public and private keys
        When I request "/users/publickey" using HTTP "POST"
        Then I should get a response with "405 Method not allowed"

    Scenario Outline: The user endpoint only supports HTTP GET and HEAD
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey.json" using HTTP "<method>"
        Then I should get a response with "<status>"

        Examples:
            | method | status                 |
            | GET    | 200 OK                 |
            | HEAD   | 200 OK                 |
            | POST   | 405 Method not allowed |
            | PUT    | 405 Method not allowed |
            | DELETE | 405 Method not allowed |
