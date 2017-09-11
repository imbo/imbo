Feature: Imbo allows plugins for outputting new file types
  In order to support new file formats
  As an HTTP Client
  I want to make requests against the image endpoint

  Background:
    Given Imbo uses the "image-output-converters.php" configuration

  Scenario: Request an image in a custom format
    Given "tests/phpunit/Fixtures/image.png" exists for user "user"
    And I use "publicKey" and "privateKey" for public and private keys
    And I include an access token in the query string
    And the "Accept" request header is "image/webp"
    When I request the previously added image
    Then the response status line is "200 OK"
    And the "Content-Type" response header is "image/webp"
    And the image dimension is "665x463"

  Scenario: Request an image in a custom format with a custom extension
    Given "tests/phpunit/Fixtures/image.png" exists for user "user"
    And I use "publicKey" and "privateKey" for public and private keys
    And I include an access token in the query string
    And the "Accept" request header is "image/webp"
    When I request the previously added image as a "webp"
    Then the response status line is "200 OK"
    And the "Content-Type" response header is "image/webp"
    And the image dimension is "665x463"