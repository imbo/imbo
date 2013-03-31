Feature: Imbo provides a status endpoint
    In order to see Imbo status
    As an HTTP Client
    I want to make requests against the status endpoint

    Scenario Outline: The status endpoint can respond with different content types
        When I request "<endpoint>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"

        Examples:
            | endpoint     | content-type     |
            | /status.json | application/json |
            | /status.xml  | application/xml  |
            | /status.html | text/html        |

    Scenario Outline: The status endpoint only supports GET and HEAD
        When I request "/status.json" using HTTP "<method>"
        Then I should get a response with "405 Method not allowed"

        Examples:
            | method |
            | POST   |
            | PUT    |
            | DELETE |

    Scenario: The status endpoint reports errors when there are issues with the database
        Given the database is down
        When I request "/status"
        Then I should get a response with "500 Database error"

    Scenario: The status endpoint reports errors when there are issues with the storage
        Given the storage is down
        When I request "/status"
        Then I should get a response with "500 Storage error"

    Scenario: The status endpoint reports errors when there are issues with both database and storage
        Given the database and the storage is down
        When I request "/status"
        Then I should get a response with "500 Database and storage error"
