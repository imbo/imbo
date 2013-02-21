Feature: Imbo provides an image endpoint
    In order to manipulate images
    As an HTTP Client
    I want to make requests against the image endpoint

    Scenario: Add an image
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And I attach "tests/Imbo/Fixtures/image1.png" to the request body
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2" using HTTP "PUT"
        Then I should get a response with "201 Created"
        And the "Content-Type" response header is "application/json"
        And the response body is:
          """
          {"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2"}
          """

    Scenario: Add an image that already exists
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And I attach "tests/Imbo/Fixtures/image1.png" to the request body
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2" using HTTP "PUT"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
          """
          {"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2"}
          """

    Scenario: Delete an image
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2" using HTTP "DELETE"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
          """
          {"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2"}
          """

    Scenario: Delete an image that does not exist
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2" using HTTP "DELETE"
        Then I should get a response with "404 Not Found"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Image not found" and the error code is "0"

    Scenario: Add a broken image
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And I attach "tests/Imbo/Fixtures/broken-image.jpg" to the request body
        When I request "/users/publickey/images/72e38ded1b41eda0c1701e6ff270eaf8" using HTTP "PUT"
        Then I should get a response with "415 Unsupported Media Type"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Broken image" and the error code is "204"
