Feature: Imbo supports custom event handlers in the configuration
    In order to add custom functionality to Imbo
    As a developer
    I can add event listeners to the Imbo configuration file

    Scenario: Register an event handler by specifying a closure
        Given the "Accept" request header is "application/json"
        When I request "/"
        Then the "X-Imbo-SomeHandler" response header matches "\d+\.\d+"
        And the "X-Imbo-SomeOtherHandler" response header matches "\d+\.\d+"

    Scenario: Register an event handler with multiple events
        Given the "Accept" request header is "application/json"
        When I request "/" using HTTP "HEAD"
        Then the "X-Imbo-SomeHandler" response header does not exist
        And the "X-Imbo-SomeOtherHandler" response header matches "\d+\.\d+"

    Scenario: Register an event handler by specifying an implementation of an event listener
        Given the "Accept" request header is "application/json"
        When I request "/"
        Then the "X-Imbo-Value1" response header is "value1"
        And the "X-Imbo-Value2" response header is "value2"

    Scenario: Register an event listener that will only trigger for some public keys
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey.json"
        Then the "X-Imbo-CurrentUser" response header is "publickey"

    Scenario: Register an event listener that will only trigger for a given public key and make a request to another key
        Given I use "user" and "key" for public and private keys
        And I include an access token in the query
        When I request "/users/user.json"
        Then the "X-Imbo-CurrentUser" response header does not exist
