Feature: Imbo supports content negotiation
    In order to get the different content types
    As an HTTP Client
    I can specify the type I want in the Accept request header

    Scenario Outline: The status endpoint can respond with different content types using content negotiation
        Given there are no Imbo issues
        And the "Accept" request header is "<accept>"
        When I request "/status"
        Then I should get a response with "200" "OK"
        And the "Content-Type" response header is "<content-type>"

        Examples:
            | accept                                                          | content-type     |
            | application/json                                                | application/json |
            | application/xml                                                 | application/xml  |
            | text/html                                                       | text/html        |
            | text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 | text/html        |
            | image/*,*/*;q=0.1                                               | application/json |
