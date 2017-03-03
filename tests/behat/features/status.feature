@resources

Feature: Imbo provides a status endpoint
    In order to see the status of an Imbo installation
    As an HTTP Client
    I want to make requests against the status endpoint

    Scenario: Fetch status information
        When I request "/status"
        Then the response code is 200
        And the response body contains JSON:
            """
            {
                "date": "@isDate()",
                "database": true,
                "storage": true
            }
            """

    Scenario Outline: The status endpoint only supports HTTP GET and HEAD
        When I request "/status" using HTTP "<method>"
        Then the response code is <code>
        And the response reason phrase is "<reason-phrase>"

        Examples:
            | method | code | reason-phrase      |
            | GET    | 200  | OK                 |
            | HEAD   | 200  | OK                 |
            | POST   | 405  | Method not allowed |
            | PUT    | 405  | Method not allowed |
            | DELETE | 405  | Method not allowed |

    Scenario: The status endpoint reports errors when there are issues with the database
        Given Imbo uses the "status.php" configuration
        And the database is down
        When I request "/status"
        Then the response code is 503
        And the response reason phrase is "Database error"
        And the response body contains JSON:
            """
            {
                "database": false,
                "storage": true
            }
            """

    Scenario: The status endpoint reports errors when there are issues with the storage
        Given Imbo uses the "status.php" configuration
        And the storage is down
        When I request "/status"
        Then the response code is 503
        And the response reason phrase is "Storage error"
        And the response body contains JSON:
            """
            {
                "database": true,
                "storage": false
            }
            """

    Scenario: The status endpoint reports errors when there are issues with both database and storage
        Given Imbo uses the "status.php" configuration
        And the storage is down
        And the database is down
        When I request "/status"
        Then the response code is 503
        And the response reason phrase is "Database and storage error"
        And the response body contains JSON:
            """
            {
                "database": false,
                "storage": false
            }
            """
