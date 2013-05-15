Feature: Imbo provides a stats endpoint
    In order to see Imbo stats
    As an HTTP Client
    I want to make requests against the stats endpoint

    Scenario Outline: The stats endpoint can respond with different content types
        When I request "<endpoint>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"

        Examples:
            | endpoint    | content-type     |
            | /stats.json | application/json |
            | /stats.xml  | application/xml  |

    Scenario Outline: The stats endpoint only supports GET and HEAD
        When I request "/stats.json" using HTTP "<method>"
        Then I should get a response with "405 Method not allowed"

        Examples:
            | method |
            | POST   |
            | PUT    |
            | DELETE |

    Scenario: The stats endpoint provides statistics
        Given "tests/Imbo/Fixtures/image1.png" exists in Imbo with identifier "fc7d2d06993047a0b5056e8fac4462a2"
        When I request "/stats.json"
        Then the response body is:
           """
           {"users":{"publickey":{"numImages":1,"numBytes":95576}},"total":{"numImages":1,"numUsers":1,"numBytes":95576}}
           """
