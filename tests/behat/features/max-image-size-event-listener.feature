Feature: Imbo provides an event listener for enforcing a max image size
    In order to ensure a maximum image size
    As an Imbo admin
    I must enable the MaxImageSize event listener

    Scenario: Add an image that is above the maximum width
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And I attach "tests/phpunit/Fixtures/1024x256.png" to the request body
        When I request "/users/publickey/images" using HTTP "POST"
        Then I should get a response with "201 Created"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
          """
          /{"imageIdentifier":"[a-z0-9]{32}","width":1000,"height":250,"extension":"png"}/
          """

    Scenario: Add an image that is above the maximum height
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And I attach "tests/phpunit/Fixtures/256x1024.png" to the request body
        When I request "/users/publickey/images" using HTTP "POST"
        Then I should get a response with "201 Created"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
          """
          /{"imageIdentifier":"[a-z0-9]{32}","width":250,"height":1000,"extension":"png"}/
          """

    Scenario: Add an image that is above the maximum width and height
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And I attach "tests/phpunit/Fixtures/1024x1024.png" to the request body
        When I request "/users/publickey/images" using HTTP "POST"
        Then I should get a response with "201 Created"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
          """
          /{"imageIdentifier":"[a-z0-9]{32}","width":1000,"height":1000,"extension":"png"}/
          """
