Feature: Imbo provides an event listener for CORS
    In order to enable CORS in Imbo
    As an Imbo admin
    I must enable the Cors event listener

    Scenario: Request a resource using an allowed host
        Given the "Origin" request header is "http://allowedhost"
        When I request "/" using HTTP "HEAD"
        Then I should get a response with "200 Hell Yeah"
        And the "Access-Control-Allow-Origin" response header is "http://allowedhost"
        And the "Access-Control-Expose-Headers" response header is "X-Imbo-Error-Internalcode"
        And the "Allow" response header is "OPTIONS, GET, HEAD"

    Scenario: Request a resource using a non-allowed host
        Given the "Origin" request header is "http://imbo"
        When I request "/" using HTTP "HEAD"
        Then I should get a response with "200 Hell Yeah"
        And the "Allow" response header is "OPTIONS, GET, HEAD"
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
        And the "Access-Control-Allow-Methods" response header is "OPTIONS, GET, HEAD"
        And the "Access-Control-Allow-Headers" response header is "Content-Type, Accept"
        And the "Access-Control-Max-Age" response header is "1349"
        And the "Allow" response header is "OPTIONS, GET, HEAD"

    Scenario: Request a resource using HTTP OPTIONS using a non-allowed host
        Given the "Origin" request header is "http://imbo"
        When I request "/" using HTTP "OPTIONS"
        Then I should get a response with "204 No Content"
        And the "Allow" response header is "OPTIONS, GET, HEAD"
        And the following response headers should not be present:
        """
        Access-Control-Allow-Origin
        Access-Control-Allow-Methods
        Access-Control-Allow-Headers
        Access-Control-Max-Age
        """
