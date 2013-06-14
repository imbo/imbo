Feature: Imbo supports custom resources
    In order to implement custom features for Imbo
    As an developer
    I can implement custom routes and resources

    Scenario: Request a custom route with a static resource name in the configuration
        Given the "Accept" request header is "application/json"
        When I request "/custom/1234567"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {"event":"custom1.get","id":"1234567"}
           """

    Scenario: Request a custom route with a closure returning the resource in the configuration
        When I request "/custom.json"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {"event":"custom2.get"}
           """

    Scenario: Request a custom route with a closure returning the resource in the configuration using PUT
        When I request "/custom.json" using HTTP "PUT"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {"event":"custom2.put"}
           """

    Scenario: Request a custom route with a closure returning the resource in the configuration with xml as an extension
        When I request "/custom.xml"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/xml"
        And the response body contains:
           """
           <event>custom2.get</event>
           """
