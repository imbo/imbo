Feature: Imbo provides an image endpoint
    In order to manipulate images
    As an HTTP Client
    I want to make requests against the image endpoint

    Scenario: Add an image
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And I attach "tests/phpunit/Fixtures/image1.png" to the request body
        When I request "/users/user/images" using HTTP "POST"
        Then I should get a response with "201 Created"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
          """
          /{"imageIdentifier":"[^"]+","width":599,"height":417,"extension":"png"}/
          """

    Scenario: Add an image that already exists
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And I attach "tests/phpunit/Fixtures/image1.png" to the request body
        When I request "/users/user/images" using HTTP "POST"
        Then I should get a response with "201 Created"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
          """
          /{"imageIdentifier":"[^"]+","width":599,"height":417,"extension":"png"}/
          """

    Scenario: Fetch image
        Given "tests/phpunit/Fixtures/image1.png" exists in Imbo
        And I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "image/png"
        When I request the previously added image
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/png"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"
        And the response body length is "95576"

    Scenario: Fetch image information when not accepting images
        Given "tests/phpunit/Fixtures/image1.png" exists in Imbo
        And I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "application/json"
        When I request the previously added image
        Then I should get a response with "406 Not acceptable"
        And the "Content-Type" response header is "application/json"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"

    Scenario: Delete an image
        Given "tests/phpunit/Fixtures/image1.png" exists in Imbo
        And I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        When I request the previously added image using HTTP "DELETE"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
          """
          /{"imageIdentifier":"[^"]+"}/
          """

    Scenario: Delete an image that does not exist
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        When I request "/users/user/images/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa" using HTTP "DELETE"
        Then I should get a response with "404 Image not found"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Image not found" and the error code is "0"

    Scenario: Add a broken image
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And I attach "tests/phpunit/Fixtures/broken-image.jpg" to the request body
        When I request "/users/user/images" using HTTP "POST"
        Then I should get a response with "415 Invalid image"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Invalid image" and the error code is "205"

    Scenario: Add a broken image with identifiable size
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And I attach "tests/phpunit/Fixtures/slightly-broken-image.png" to the request body
        When I request "/users/user/images" using HTTP "POST"
        Then I should get a response with "415 Invalid image"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Invalid image" and the error code is "205"
