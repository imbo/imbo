@resources

Feature: Imbo provides an image endpoint
    In order to manipulate images
    As an HTTP Client
    I want to make requests against the image endpoint

    Scenario: Add an image
        Given the request body contains "tests/phpunit/Fixtures/image1.png"
        And I sign the request with "publickey" and "privatekey"
        When I request "/users/user/images" using HTTP "POST"
        Then the response code is 201
        And the response reason phrase is "Created"
        And the "Content-Type" response header is "application/json"
        And the response body contains:
          """
          {
              "imageIdentifier":"<re>/^[a-zA-Z0-9_-]{12}$/</re>",
              "width": 599,
              "height": 417,
              "extension": "png"
          }
          """

    Scenario: Add an image that already exists
        Given "tests/phpunit/Fixtures/image1.png" exists for user "user" in Imbo
        And the request body contains "tests/phpunit/Fixtures/image1.png"
        And I sign the request with "publickey" and "privatekey"
        When I request "/users/user/images" using HTTP "POST"
        Then the response code is 201
        And the response reason phrase is "Created"
        And the "Content-Type" response header is "application/json"
        And the response body contains:
          """
          {
              "imageIdentifier":"<re>/^[a-zA-Z0-9_-]{12}$/</re>",
              "width": 599,
              "height": 417,
              "extension": "png"
          }
          """

    Scenario: Fetch image
        Given "tests/phpunit/Fixtures/image1.png" exists for user "user" in Imbo
        And I include an access token in the query using "publickey" and "privatekey"
        And the "Accept" request header is "image/png"
        When I request the previously added image
        Then the response code is 200
        And the response reason phrase is "OK"
        And the "Content-Type" response header is "image/png"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"
        And the "Content-Length" response header is "95576"
        And the response body size is "95576"

    Scenario: Fetch image information when not accepting images
        Given "tests/phpunit/Fixtures/image1.png" exists for user "user" in Imbo
        And I include an access token in the query using "publickey" and "privatekey"
        And the "Accept" request header is "application/json"
        When I request the previously added image
        Then the response code is 406
        And the response reason phrase is "Not acceptable"
        And the "Content-Type" response header is "application/json"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"

    Scenario: Delete an image
        Given "tests/phpunit/Fixtures/image1.png" exists for user "user" in Imbo
        And I sign the request with "publickey" and "privatekey"
        When I request the previously added image using HTTP "DELETE"
        Then the response code is 200
        And the response reason phrase is "OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains:
          """
          {
              "imageIdentifier":"<re>/^[a-zA-Z0-9_-]{12}$/</re>"
          }
          """

    Scenario: Delete an image that does not exist
        Given I sign the request with "publickey" and "privatekey"
        When I request "/users/user/images/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa" using HTTP "DELETE"
        Then the response code is 404
        And the response reason phrase is "Image not found"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Image not found" and the error code is "0"

    Scenario: Add a broken image
        Given I sign the request with "publickey" and "privatekey"
        And the request body contains "tests/phpunit/Fixtures/broken-image.jpg"
        When I request "/users/user/images" using HTTP "POST"
        Then the response code is 415
        And the response reason phrase is "Invalid image"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Invalid image" and the error code is "205"

    Scenario: Add a broken image with identifiable size
        Given I sign the request with "publickey" and "privatekey"
        And the request body contains "tests/phpunit/Fixtures/slightly-broken-image.png"
        When I request "/users/user/images" using HTTP "POST"
        Then the response code is 415
        And the response reason phrase is "Invalid image"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Invalid image" and the error code is "205"
