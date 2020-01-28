Feature: Imbo supports custom resources
    In order to implement custom features for Imbo
    As an developer
    I can implement custom routes and resources and add them to the server configuration

    Scenario: Request a custom route specified in the configuration
        Given the "Accept" request header is "application/json"
        And Imbo uses the "custom-routes-and-resources.php" configuration
        When I request "/custom/1234567"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {"event":"custom1.get","id":"1234567"}
           """

    Scenario: Request a custom route with a closure returning the resource in the configuration
        Given Imbo uses the "custom-routes-and-resources.php" configuration
        When I request "/custom.json"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {"event":"custom2.get"}
           """

    Scenario: Request a custom route with a closure returning the resource in the configuration using PUT
        Given Imbo uses the "custom-routes-and-resources.php" configuration
        When I request "/custom.json" using HTTP "PUT"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {"event":"custom2.put"}
           """
