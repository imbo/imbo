Feature: Imbo provides a status endpoint
    In order to see the status of an Imbo installation
    As an HTTP Client
    I want to make requests against the status endpoint

    Scenario Outline: Fetch status information
        When I request "/status.<extension>"
        Then I should get a response with "200 OK"
        And the response body matches:
            """
            <response>
            """

        Examples:
            | extension | response |
            | json      | #^{"date":"[^"]+","database":true,"storage":true}$# |
            | xml       | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<status>\s*<date>[^<]+</date>\s*<database>1</database>\s*<storage>1</storage>\s*</status>\s*</imbo>$#ms |

    Scenario Outline: The status endpoint only supports HTTP GET and HEAD
        When I request "/status.json" using HTTP "<method>"
        Then I should get a response with "<status>"

        Examples:
            | method | status                 |
            | GET    | 200 OK                 |
            | HEAD   | 200 OK                 |
            | POST   | 405 Method not allowed |
            | PUT    | 405 Method not allowed |
            | DELETE | 405 Method not allowed |
            | SEARCH | 405 Method not allowed |

    Scenario: The status endpoint reports errors when there are issues with the database
        Given Imbo uses the "status.php" configuration
        And the database is down
        When I request "/status"
        Then I should get a response with "503 Database error"

    Scenario: The status endpoint reports errors when there are issues with the storage
        Given Imbo uses the "status.php" configuration
        And the storage is down
        When I request "/status"
        Then I should get a response with "503 Storage error"

    Scenario: The status endpoint reports errors when there are issues with both database and storage
        Given Imbo uses the "status.php" configuration
        And the database and the storage is down
        When I request "/status"
        Then I should get a response with "503 Database and storage error"
