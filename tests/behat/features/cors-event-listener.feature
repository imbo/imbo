Feature: Imbo provides an event listener for CORS
    In order to enable CORS in Imbo
    As an Imbo admin
    I must enable the Cors event listener

    Scenario: Request a resource using an allowed host
        Given the "Origin" request header is "http://allowedhost"
        And Imbo uses the "cors.php" configuration
        When I request "/" using HTTP "HEAD"
        Then I should get a response with "200 Hell Yeah"
        And the "Access-Control-Allow-Origin" response header is "http://allowedhost"
        And the "Access-Control-Expose-Headers" response header contains "X-Imbo-ImageIdentifier"
        And the "Access-Control-Expose-Headers" response header contains "X-Imbo-Version"
        And the "Vary" response header contains "Origin"
        And the "Allow" response header contains "GET"
        And the "Allow" response header contains "HEAD"
        And the "Allow" response header contains "OPTIONS"

    Scenario: Request a resource using a non-allowed host
        Given the "Origin" request header is "http://imbo"
        And Imbo uses the "cors.php" configuration
        When I request "/" using HTTP "HEAD"
        Then I should get a response with "200 Hell Yeah"
        And the "Vary" response header contains "Origin"
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
        And the "Access-Control-Request-Headers" request header is "x-imbo-something, x-imbo-signature"
        And Imbo uses the "cors.php" configuration
        When I request "/" using HTTP "OPTIONS"
        Then I should get a response with "204 No Content"
        And the "Access-Control-Allow-Origin" response header is "http://allowedhost"
        And the "Access-Control-Allow-Methods" response header contains "GET"
        And the "Access-Control-Allow-Methods" response header contains "HEAD"
        And the "Access-Control-Allow-Methods" response header contains "OPTIONS"
        And the "Access-Control-Allow-Headers" response header contains "Accept"
        And the "Access-Control-Allow-Headers" response header contains "Content-Type"
        And the "Access-Control-Allow-Headers" response header contains "X-Imbo-Signature"
        And the "Access-Control-Allow-Headers" response header contains "X-Imbo-Something"
        And the "Access-Control-Max-Age" response header is "1349"
        And the "Vary" response header contains "Origin"
        And the "Allow" response header contains "GET"
        And the "Allow" response header contains "HEAD"
        And the "Allow" response header contains "OPTIONS"

    Scenario: Request a resource using HTTP OPTIONS using a non-allowed host
        Given the "Origin" request header is "http://imbo"
        And Imbo uses the "cors.php" configuration
        When I request "/" using HTTP "OPTIONS"
        Then I should get a response with "204 No Content"
        And the "Vary" response header contains "Origin"
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

    Scenario: Provides CORS headers when applications fails
        Given I use "publickey" and "privatekey" for public and private keys
        And Imbo uses the "cors.php" configuration
        And the "Origin" request header is "http://allowedhost"
        And I sign the request
        And I attach "ChangeLog.markdown" to the request body
        When I request "/users/user/images" using HTTP "POST"
        Then I should get a response with "415 Unsupported image type: text/plain"
        And the "Vary" response header contains "Origin"
        And the following response headers should be present:
        """
        Access-Control-Allow-Origin
        """

    Scenario: Provides CORS headers when authentication fails
        Given I use "invalid-pubkey" and "invalid-privkey" for public and private keys
        And Imbo uses the "cors.php" configuration
        And the "Origin" request header is "http://allowedhost"
        When I request "/users/user/images" using HTTP "GET"
        Then I should get a response with "400 Permission denied (public key)"
        And the following response headers should be present:
        """
        Access-Control-Allow-Origin
        """

    Scenario: Request a resource using HTTP OPTIONS without an Origin header when all origins are accepted
        Given Imbo uses the "cors-wildcard.php" configuration
        When I request "/" using HTTP "OPTIONS"
        Then I should get a response with "204 No Content"
        And the "Vary" response header contains "Origin"
        And the following response headers should not be present:
        """
        Access-Control-Allow-Origin
        Access-Control-Allow-Methods
        Access-Control-Allow-Headers
        Access-Control-Max-Age
        """

    Scenario: Request a resource without an Origin header when all origins are accepted
        Given Imbo uses the "cors-wildcard.php" configuration
        When I request "/" using HTTP "GET"
        Then I should get a response with "200 Hell Yeah"
        And the "Vary" response header contains "Origin"
        And the following response headers should not be present:
        """
        Access-Control-Allow-Origin
        Access-Control-Allow-Methods
        Access-Control-Allow-Headers
        Access-Control-Max-Age
        """
