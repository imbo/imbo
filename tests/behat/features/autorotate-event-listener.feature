Feature: Imbo provides an event listener for auto rotating images based on EXIF-data
    In order to ensure a correct image rotation of new images
    As an Imbo admin
    I must enable the AutoRotate event listener

    Background:
        Given Imbo uses the "auto-rotate-added-images.php" configuration
        And "tests/phpunit/Fixtures/640x160_rotated.jpg" exists for user "user"

    Scenario: Fetch the auto rotated image
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "image/png"
        And the image dimension is "640x160"
