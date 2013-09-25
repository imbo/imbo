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

#    Scenario: Register an event handler by specifying an implementation of an event listener
#    Scenario: Register an event listener that will only trigger for some public keys
