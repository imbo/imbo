Feature: Imbo supports content negotiation
    In order to get the different content types
    As an HTTP Client
    I can specify the type I want in the Accept request header

    Scenario Outline: The status endpoint can respond with different content types using content negotiation
        Given the "Accept" request header is "<accept>"
        When I request "/status"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"

        Examples:
            | accept                                                          | content-type     |
            | application/json                                                | application/json |
            | application/xml                                                 | application/xml  |
            | text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 | application/xml  |
            | image/*,*/*;q=0.1                                               | application/json |

    Scenario: If the client includes an extension, the Accept header should be ignored
        Given the "Accept" request header is "application/xml"
        When I request "/status.json"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"

    Scenario: If the server responds with an error, and the client included a valid extension, that type should be returned
        Given the "Accept" request header is "application/xml"
        When I request "/users/foobar.json"
        Then I should get a response with "404 Unknown public key"
        And the "Content-Type" response header is "application/json"
