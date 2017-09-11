Feature: Imbo allows plugins for loading new file types
  In order to support new file formats
  As an HTTP Client
  I want to make requests against the image endpoint

  Background:
    Given Imbo uses the "image-loaders.php" configuration

  Scenario: Add an image
    Given the request body contains "tests/phpunit/Fixtures/nasa_sts-64.tif"
    And I use "publicKey" and "privateKey" for public and private keys
    And I sign the request
    When I request "/users/user/images" using HTTP "POST"
    Then the response status line is "201 Created"
    And the "Content-Type" response header is "application/json"
    And the response body contains JSON:
          """
          {
              "imageIdentifier": "@regExp(/^[a-zA-Z0-9_-]+$/)",
              "width": 437,
              "height": 640,
              "extension": "tif"
          }
          """

  Scenario: Request an image loaded by a custom plugin
    Given "tests/phpunit/Fixtures/nasa_sts-64.tif" exists for user "user"
    And I use "publicKey" and "privateKey" for public and private keys
    And I include an access token in the query string
    And the "Accept" request header is "image/png"
    When I request the previously added image
    Then the response status line is "200 OK"
    And the "Content-Type" response header is "image/png"
    And the "X-Imbo-Originalextension" response header is "tif"
    And the "X-Imbo-Originalheight" response header is "640"
    And the "X-Imbo-Originalmimetype" response header is "image/tiff"
    And the "X-Imbo-Originalwidth" response header is "437"

  Scenario: Add an 'image' that isn't supported by imagick
    Given the request body contains "tests/behat/Fixtures/foobar.txt"
    And I use "publicKey" and "privateKey" for public and private keys
    And I sign the request
    When I request "/users/user/images" using HTTP "POST"
    Then the response status line is "201 Created"
    And the "Content-Type" response header is "application/json"
    And the response body contains JSON:
          """
          {
              "imageIdentifier": "@regExp(/^[a-zA-Z0-9_-]+$/)",
              "width": 300,
              "height": 300,
              "extension": "txt"
          }
          """