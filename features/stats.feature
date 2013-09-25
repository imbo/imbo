Feature: Imbo provides a stats endpoint
    In order to see Imbo stats
    As an HTTP Client
    I want to make requests against the stats endpoint

    Background:
        Given "tests/Imbo/Fixtures/image1.png" exists in Imbo with identifier "fc7d2d06993047a0b5056e8fac4462a2"
        And "tests/Imbo/Fixtures/image.jpg" exists in Imbo with identifier "f3210f1bb34bfbfa432cc3560be40761"
        And "tests/Imbo/Fixtures/image.gif" exists in Imbo with identifier "b5426b4c008e378c201526d2baaec599"

    Scenario Outline: Fetch stats
        When I request "/stats.<extension>"
        Then I should get a response with "200 OK"
        And the response body <match>:
            """
            <response>
            """

        Examples:
            | extension | match   | response |
            | json      | is      | {"users":{"publickey":{"numImages":3,"numBytes":226424},"user":{"numImages":0,"numBytes":0}},"total":{"numImages":3,"numUsers":2,"numBytes":226424},"custom":{}} |
            | xml       | matches | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<stats>\s*<users>\s*<user publicKey="publickey">\s*<numImages>3</numImages>\s*<numBytes>226424</numBytes>\s*</user>\s*<user publicKey="user">\s*<numImages>0</numImages>\s*<numBytes>0</numBytes>\s*</user>\s*</users>\s*<total>\s*<numImages>3</numImages>\s*<numBytes>226424</numBytes>\s*<numUsers>2</numUsers>\s*</total>\s*<custom></custom>\s*</stats>\s*</imbo>$#ms |

    Scenario Outline: The stats endpoint only supports HTTP GET and HEAD
        When I request "/stats.json" using HTTP "<method>"
        Then I should get a response with "<status>"

        Examples:
            | method | status                 |
            | GET    | 200 OK                 |
            | HEAD   | 200 OK                 |
            | POST   | 405 Method not allowed |
            | PUT    | 405 Method not allowed |
            | DELETE | 405 Method not allowed |
