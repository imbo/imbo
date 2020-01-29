@resources

Feature: Imbo provides a stats endpoint
    In order to see Imbo stats
    As an HTTP Client
    I want to make requests against the stats endpoint

    Scenario: Fetch stats
        Given "tests/Fixtures/image1.png" exists for user "user"
        And "tests/Fixtures/image.jpg" exists for user "user"
        And "tests/Fixtures/image.gif" exists for user "user"
        And Imbo uses the "stats-access-and-custom-stats.php" configuration
        And the stats are allowed by "*"
        When I request "/stats"
        Then the response code is 200
        And the response body contains JSON:
            """
            {
                "numImages": 3,
                "numUsers": 1,
                "numBytes": 226424,
                "custom": {
                    "someValue": 123,
                    "someOtherValue": {
                        "foo": "bar"
                    },
                    "someList": [
                        1, 2, 3
                    ]
                }
            }
            """

    Scenario Outline: The stats endpoint only supports HTTP GET and HEAD
        Given Imbo uses the "stats-access-and-custom-stats.php" configuration
        And the stats are allowed by "*"
        When I request "/stats" using HTTP "<method>"
        Then the response code is <code>
        And the response reason phrase is "<reasonPhrase>"

        Examples:
            | method | code | reasonPhrase       |
            | GET    | 200  | OK                 |
            | HEAD   | 200  | OK                 |
            | POST   | 405  | Method not allowed |
            | PUT    | 405  | Method not allowed |
            | DELETE | 405  | Method not allowed |

    Scenario Outline: Stats access event listener decides the access level for the stats endpoint
        Given Imbo uses the "stats-access-and-custom-stats.php" configuration
        And the stats are allowed by "<allow>"
        And the client IP is "<client-ip>"
        When I request "/stats"
        Then the response code is <code>
        And the response reason phrase is "<reasonPhrase>"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
            """
            /^{.*}$/ms
            """

        Examples:
            | client-ip | allow             | code | reasonPhrase  |
            | 127.0.0.1 | 10.0.0.0          | 403  | Access denied |
            | 127.0.0.1 | 2001:db8::/48     | 403  | Access denied |
            | ::1       | 2001:db8::/48     | 403  | Access denied |
            | ::1       | 127.0.0.1         | 403  | Access denied |
            | 127.0.0.1 | 127.0.0.1,::1     | 200  | OK            |
            | ::1       | 127.0.0.1,::1     | 200  | OK            |
            | ::1       | *                 | 200  | OK            |

    Scenario Outline: Stats access event listener authenticates HEAD requests as well
        Given Imbo uses the "stats-access-and-custom-stats.php" configuration
        And the stats are allowed by "<allow>"
        And the client IP is "<client-ip>"
        When I request "/stats" using HTTP "HEAD"
        Then the response code is <code>
        And the response reason phrase is "<reasonPhrase>"
        And the "Content-Type" response header is "application/json"

        Examples:
            | client-ip | allow             | code | reasonPhrase  |
            | 127.0.0.1 | 10.0.0.0          | 403  | Access denied |
            | 127.0.0.1 | 2001:db8::/48     | 403  | Access denied |
            | ::1       | 2001:db8::/48     | 403  | Access denied |
            | ::1       | 127.0.0.1         | 403  | Access denied |
            | 127.0.0.1 | 127.0.0.1,::1     | 200  | OK            |
            | ::1       | 127.0.0.1,::1     | 200  | OK            |
            | ::1       | *                 | 200  | OK            |
