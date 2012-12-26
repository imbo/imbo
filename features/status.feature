Feature: Imbo provides a status endpoint
    In order to see Imbo status
    As an HTTP Client
    I want to make requests against the status endpoint

    Scenario Outline: The status endpoint can respond with different content types
        Given there are no Imbo issues
        When I request "<endpoint>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"

        Examples:
            | endpoint     | content-type     |
            | /status.json | application/json |
            | /status.xml  | application/xml  |
            | /status.html | text/html        |

    Scenario Outline: The status endpoint only supports GET and HEAD
        Given there are no Imbo issues
        When I request "/status.json" using HTTP "<method>"
        Then I should get a response with "405 Method Not Allowed"

        Examples:
            | method |
            | POST   |
            | PUT    |
            | DELETE |
