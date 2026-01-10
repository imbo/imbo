@resources

Feature: Imbo provides an index endpoint
    In order to see basic Imbo information
    As an HTTP Client
    I want to make requests against the index endpoint

    Scenario: Fetch index
        When I request "/"
        Then the response code is 200
        And the response reason phrase is "Hell Yeah"
        And the response body contains JSON:
            """
            {
                "site": "https://imbo.io"
            }
            """

    Scenario Outline: The index endpoint only supports HTTP GET and HEAD
        When I request "/" using HTTP "<method>"
        Then the response code is <code>
        And the response reason phrase is "<reasonPhrase>"

        Examples:
            | method | code | reasonPhrase       |
            | GET    | 200  | Hell Yeah          |
            | HEAD   | 200  | Hell Yeah          |
            | POST   | 405  | Method not allowed |
            | PUT    | 405  | Method not allowed |
            | DELETE | 405  | Method not allowed |
