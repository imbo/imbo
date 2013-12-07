Feature: Imbo provides an event listener for CORS
    In order to enable CORS in Imbo
    As an Imbo admin
    I must enable the Cors event listener

    Scenario: Request a resource using an allowed host
        Given the "Origin" request header is "http://allowedhost"
        When I request "/" using HTTP "HEAD"
        Then I should get a response with "200 Hell Yeah"
        And the "Access-Control-Allow-Origin" response header is "http://allowedhost"
        And the "Access-Control-Expose-Headers" response header contains "X-Imbo-ImageIdentifier"
        And the "Access-Control-Expose-Headers" response header contains "X-Imbo-Version"
        And the "Allow" response header contains "GET"
        And the "Allow" response header contains "HEAD"
        And the "Allow" response header contains "OPTIONS"

    Scenario: Request a resource using a non-allowed host
        Given the "Origin" request header is "http://imbo"
        When I request "/" using HTTP "HEAD"
        Then I should get a response with "200 Hell Yeah"
        And the "Allow" response header contains "GET"
        And the "Allow" response header contains "HEAD"
        And the "Allow" response header contains "OPTIONS"
        And the following response headers should not be present:
        """
        Access-Control-Allow-Origin
        Access-Control-Expose-Headers
        """

    Scenario: Request a resource using HTTP OPTIONS using an allowed host
        Given the "Origin" request header is "http://allowedhost"
        When I request "/" using HTTP "OPTIONS"
        Then I should get a response with "204 No Content"
        And the "Access-Control-Allow-Origin" response header is "http://allowedhost"
        And the "Access-Control-Allow-Methods" response header contains "GET"
        And the "Access-Control-Allow-Methods" response header contains "HEAD"
        And the "Access-Control-Allow-Methods" response header contains "OPTIONS"
        And the "Access-Control-Allow-Headers" response header contains "Accept"
        And the "Access-Control-Allow-Headers" response header contains "Content-Type"
        And the "Access-Control-Max-Age" response header is "1349"
        And the "Allow" response header contains "GET"
        And the "Allow" response header contains "HEAD"
        And the "Allow" response header contains "OPTIONS"

    Scenario: Request a resource using HTTP OPTIONS using a non-allowed host
        Given the "Origin" request header is "http://imbo"
        When I request "/" using HTTP "OPTIONS"
        Then I should get a response with "204 No Content"
        And the "Allow" response header contains "GET"
        And the "Allow" response header contains "HEAD"
        And the "Allow" response header contains "OPTIONS"
        And the following response headers should not be present:
        """
        Access-Control-Allow-Origin
        Access-Control-Allow-Methods
        Access-Control-Allow-Headers
        Access-Control-Max-Age
        """
