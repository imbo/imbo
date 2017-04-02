Feature: Imbo provides an event listener for enforcing a max image size
    In order to ensure a maximum image size
    As an Imbo admin
    I must enable the MaxImageSize event listener

    Background:
        Given I use "publicKey" and "privateKey" for public and private keys
        And I sign the request
        And Imbo uses the "enforce-max-image-size.php" configuration

    Scenario: Add an image that is above the maximum width
        Given the request body contains "tests/phpunit/Fixtures/1024x256.png"
        When I request "/users/user/images" using HTTP "POST"
        Then the response status line is "201 Created"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
          """
          {
            "imageIdentifier": "@regExp(/^[a-zA-Z0-9-_]{12}$/)",
            "width": 1000,
            "height": 250,
            "extension":"png"
          }
          """

    Scenario: Add an image that is above the maximum height
        Given the request body contains "tests/phpunit/Fixtures/256x1024.png"
        When I request "/users/user/images" using HTTP "POST"
        Then the response status line is "201 Created"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
          """
          {
            "imageIdentifier": "@regExp(/^[a-zA-Z0-9-_]{12}$/)",
            "width": 150,
            "height": 600,
            "extension":"png"
          }
          """

    Scenario: Add an image that is above the maximum width and height
        Given the request body contains "tests/phpunit/Fixtures/1024x1024.png"
        When I request "/users/user/images" using HTTP "POST"
        Then the response status line is "201 Created"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
          """
          {
            "imageIdentifier": "@regExp(/^[a-zA-Z0-9-_]{12}$/)",
            "width": 600,
            "height": 600,
            "extension":"png"
          }
          """
