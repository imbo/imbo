Feature: Imbo provides a stats endpoint
    In order to see Imbo stats
    As an HTTP Client
    I want to make requests against the stats endpoint

    Background:
        Given "tests/phpunit/Fixtures/image1.png" exists in Imbo
        And "tests/phpunit/Fixtures/image.jpg" exists in Imbo
        And "tests/phpunit/Fixtures/image.gif" exists in Imbo

    Scenario Outline: Fetch stats
        Given Imbo uses the "stats-access-and-custom-stats.php" configuration
        When I request "/stats.<extension>?statsAllow=*"
        Then I should get a response with "200 OK"
        And the response body matches:
            """
            <response>
            """

        Examples:
            | extension | response |
            | json      | {"users":{"publickey":{"numImages":3,"numBytes":226424},"user":{"numImages":0,"numBytes":0}},"total":{"numImages":3,"numUsers":2,"numBytes":226424},"custom":{.*}} |
            | xml       | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<stats>\s*<users>\s*<user id="publickey">\s*<numImages>3</numImages>\s*<numBytes>226424</numBytes>\s*</user>\s*<user id="user">\s*<numImages>0</numImages>\s*<numBytes>0</numBytes>\s*</user>\s*</users>\s*<total>\s*<numImages>3</numImages>\s*<numBytes>226424</numBytes>\s*<numUsers>2</numUsers>\s*</total>\s*<custom>.*</custom>\s*</stats>\s*</imbo>$#ms |

    Scenario Outline: The stats endpoint only supports HTTP GET and HEAD
        Given Imbo uses the "stats-access-and-custom-stats.php" configuration
        When I request "/stats.json?statsAllow=*" using HTTP "<method>"
        Then I should get a response with "<status>"

        Examples:
            | method | status                 |
            | GET    | 200 OK                 |
            | HEAD   | 200 OK                 |
            | POST   | 405 Method not allowed |
            | PUT    | 405 Method not allowed |
            | DELETE | 405 Method not allowed |
            | SEARCH | 405 Method not allowed |

    Scenario Outline: Stats access event listener decides the access level for the stats endpoint
        Given Imbo uses the "stats-access-and-custom-stats.php" configuration
        And the client IP is "<client-ip>"
        When I request "/stats.json?statsAllow=<allow>"
        Then I should get a response with "<status>"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
            """
            #^{.*}$#ms
            """

        Examples:
            | client-ip | allow             | status            |
            | 127.0.0.1 | 10.0.0.0          | 403 Access denied |
            | 127.0.0.1 | 2001:db8::/48     | 403 Access denied |
            | ::1       | 2001:db8::/48     | 403 Access denied |
            | ::1       | 127.0.0.1         | 403 Access denied |
            | 127.0.0.1 | 127.0.0.1,::1     | 200 OK            |
            | ::1       | 127.0.0.1,::1     | 200 OK            |
            | ::1       | *                 | 200 OK            |

    Scenario Outline: Stats access event listener authenticates HEAD requests as well
        Given Imbo uses the "stats-access-and-custom-stats.php" configuration
        And the client IP is "<client-ip>"
        When I request "/stats.json?statsAllow=<allow>" using HTTP "HEAD"
        Then I should get a response with "<status>"
        And the "Content-Type" response header is "application/json"

        Examples:
            | client-ip | allow             | status            |
            | 127.0.0.1 | 10.0.0.0          | 403 Access denied |
            | 127.0.0.1 | 2001:db8::/48     | 403 Access denied |
            | ::1       | 2001:db8::/48     | 403 Access denied |
            | ::1       | 127.0.0.1         | 403 Access denied |
            | 127.0.0.1 | 127.0.0.1,::1     | 200 OK            |
            | ::1       | 127.0.0.1,::1     | 200 OK            |
            | ::1       | *                 | 200 OK            |

    Scenario Outline: Custom statistics can be added through an event listener
        Given Imbo uses the "stats-access-and-custom-stats.php" configuration
        When I request "/stats.<extension>?statsAllow=*"
        Then I should get a response with "200 OK"
        And the response body matches:
            """
            <response>
            """

        Examples:
            | extension | response |
            | json      | {.*?,"custom":{"someValue":123,"someOtherValue":{"foo":"bar"},"someList":\[1,2,3\]}} |
            | xml       | #^<\?xml version="1.0" encoding="UTF-8"\?>.*?<custom><someValue>123</someValue><someOtherValue><foo>bar</foo></someOtherValue><someList><list><value>1</value><value>2</value><value>3</value></list></someList></custom>\s*</stats>\s*</imbo>$#ms |
