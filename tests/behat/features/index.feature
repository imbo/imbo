Feature: Imbo provides an index endpoint
    In order to see the Imbo version
    As an HTTP Client
    I want to make requests against the index endpoint

    Scenario Outline: Fetch index
        Given the "Accept" request header is "<accept>"
        When I request "/"
        Then I should get a response with "200 Hell Yeah"
        And the response body matches:
            """
            <response>
            """

        Examples:
            | accept           | response |
            | application/json | #^{"version":"[^"]+".*?}$#    |
            | application/xml  | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo><version>[^>]+</version>.*?</imbo>$#ms |

    Scenario Outline: The index endpoint only supports HTTP GET and HEAD
        When I request "/" using HTTP "<method>"
        Then I should get a response with "<status>"

        Examples:
            | method | status                 |
            | GET    | 200 Hell Yeah          |
            | HEAD   | 200 Hell Yeah          |
            | POST   | 405 Method not allowed |
            | PUT    | 405 Method not allowed |
            | DELETE | 405 Method not allowed |
            | SEARCH | 405 Method not allowed |
