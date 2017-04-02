Feature: Imbo supports generation of md5 image identifiers
    In order to insert images
    As an HTTP Client
    I want to make requests against the image endpoint

    Background:
        Given Imbo uses the "image-identifier-md5.php" configuration

    Scenario: Add a new image
        Given I use "publicKey" and "privateKey" for public and private keys
        And I sign the request
        And the request body contains "tests/phpunit/Fixtures/image.jpg"
        When I request "/users/user/images" using HTTP "POST"
        Then the response status line is "201 Created"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
              "imageIdentifier": "f3210f1bb34bfbfa432cc3560be40761",
              "width": 665,
              "height": 463,
              "extension": "jpg"
            }
            """

    Scenario: Add an image that already exists
        Given "tests/phpunit/Fixtures/image1.png" exists for user "user"
        And I use "publicKey" and "privateKey" for public and private keys
        And I sign the request
        And the request body contains "tests/phpunit/Fixtures/image1.png"
        When I request "/users/user/images" using HTTP "POST"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
              "imageIdentifier": "fc7d2d06993047a0b5056e8fac4462a2",
              "width": 599,
              "height": 417,
              "extension": "png"
            }
            """
