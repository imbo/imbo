Feature: Imbo provides an event listener for CORS
    In order to enable CORS in Imbo
    As an Imbo admin
    I must enable the Cors event listener

    Scenario: Request a resource using an allowed host
        Given the "Origin" request header is "http://allowedhost"
        And Imbo uses the "cors.php" configuration
        When I request "/" using HTTP "HEAD"
        Then the response status line is "200 Hell Yeah"
        And the "Access-Control-Allow-Origin" response header is "http://allowedhost"
        And the "Access-Control-Expose-Headers" response header matches "/X-Imbo-ImageIdentifier/"
        And the "Vary" response header matches "/Origin/"
        And the "Allow" response header matches "/GET/"
        And the "Allow" response header matches "/HEAD/"
        And the "Allow" response header matches "/OPTIONS/"

    Scenario: Request a resource using a non-allowed host
        Given the "Origin" request header is "http://imbo"
        And Imbo uses the "cors.php" configuration
        When I request "/" using HTTP "HEAD"
        Then the response status line is "200 Hell Yeah"
        And the "Vary" response header matches "/Origin/"
        And the "Allow" response header matches "/GET/"
        And the "Allow" response header matches "/HEAD/"
        And the "Allow" response header matches "/OPTIONS/"
        And the "Access-Control-Allow-Origin" response header does not exist
        And the "Access-Control-Expose-Headers" response header does not exist

    Scenario: Request a resource using HTTP OPTIONS using an allowed host
        Given the "Origin" request header is "http://allowedhost"
        And the "Access-Control-Request-Headers" request header is "x-imbo-something, x-imbo-signature"
        And Imbo uses the "cors.php" configuration
        When I request "/" using HTTP "OPTIONS"
        Then the response status line is "204 No Content"
        And the "Access-Control-Allow-Origin" response header is "http://allowedhost"
        And the "Access-Control-Allow-Methods" response header matches "/GET/"
        And the "Access-Control-Allow-Methods" response header matches "/HEAD/"
        And the "Access-Control-Allow-Methods" response header matches "/OPTIONS/"
        And the "Access-Control-Allow-Headers" response header matches "/Accept/"
        And the "Access-Control-Allow-Headers" response header matches "/Content-Type/"
        And the "Access-Control-Allow-Headers" response header matches "/X-Imbo-Signature/"
        And the "Access-Control-Allow-Headers" response header matches "/X-Imbo-Something/"
        And the "Access-Control-Max-Age" response header is "1349"
        And the "Vary" response header matches "/Origin/"
        And the "Allow" response header matches "/GET/"
        And the "Allow" response header matches "/HEAD/"
        And the "Allow" response header matches "/OPTIONS/"

    Scenario: Request a resource using HTTP OPTIONS using a non-allowed host
        Given the "Origin" request header is "http://imbo"
        And Imbo uses the "cors.php" configuration
        When I request "/" using HTTP "OPTIONS"
        Then the response status line is "204 No Content"
        And the "Vary" response header matches "/Origin/"
        And the "Allow" response header matches "/GET/"
        And the "Allow" response header matches "/HEAD/"
        And the "Allow" response header matches "/OPTIONS/"
        And the "Access-Control-Allow-Origin" response header does not exist
        And the "Access-Control-Allow-Methods" response header does not exist
        And the "Access-Control-Allow-Headers" response header does not exist
        And the "Access-Control-Max-Age" response header does not exist

    Scenario: Provides CORS headers when applications fails
        Given I use "publicKey" and "privateKey" for public and private keys
        And Imbo uses the "cors.php" configuration
        And the "Origin" request header is "http://allowedhost"
        And I sign the request
        And the request body contains "ChangeLog.md"
        When I request "/users/user/images" using HTTP "POST"
        Then the response status line is "415 Unsupported image type: text/plain"
        And the "Vary" response header matches "/Origin/"
        And the "Access-Control-Allow-Origin" response header exists

    Scenario: Provides CORS headers when authentication fails
        Given I use "invalid-pubkey" and "invalid-privkey" for public and private keys
        And Imbo uses the "cors.php" configuration
        And the "Origin" request header is "http://allowedhost"
        When I request "/users/user/images" using HTTP "GET"
        Then the response status line is "400 Permission denied (public key)"
        And the "Access-Control-Allow-Origin" response header exists

    Scenario: Request a resource using HTTP OPTIONS without an Origin header when all origins are accepted
        Given Imbo uses the "cors-wildcard.php" configuration
        When I request "/" using HTTP "OPTIONS"
        Then the response status line is "204 No Content"
        And the "Vary" response header matches "/Origin/"
        And the "Access-Control-Allow-Origin" response header does not exist
        And the "Access-Control-Allow-Methods" response header does not exist
        And the "Access-Control-Allow-Headers" response header does not exist
        And the "Access-Control-Max-Age" response header does not exist

    Scenario: Request a resource without an Origin header when all origins are accepted
        Given Imbo uses the "cors-wildcard.php" configuration
        When I request "/" using HTTP "GET"
        Then the response status line is "200 Hell Yeah"
        And the "Vary" response header matches "/Origin/"
        And the "Access-Control-Allow-Origin" response header does not exist
        And the "Access-Control-Allow-Methods" response header does not exist
        And the "Access-Control-Allow-Headers" response header does not exist
        And the "Access-Control-Max-Age" response header does not exist
