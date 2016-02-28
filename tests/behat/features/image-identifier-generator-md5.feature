Feature: Imbo supports generation of md5 image identifiers
    In order to insert images
    As an HTTP Client
    I want to make requests against the image endpoint

    Background:
        Given Imbo uses the "image-identifier-md5.php" configuration
        And "tests/phpunit/Fixtures/image1.png" exists in Imbo

    Scenario: Add a new image
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And I attach "tests/phpunit/Fixtures/image.jpg" to the request body
        When I request "/users/user/images" using HTTP "POST"
        Then I should get a response with "201 Created"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
          """
          /{"imageIdentifier":"f3210f1bb34bfbfa432cc3560be40761".*}/
          """

    Scenario: Add an image that already exists
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And I attach "tests/phpunit/Fixtures/image1.png" to the request body
        When I request "/users/user/images" using HTTP "POST"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
          """
          /{"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2".*}/
          """
