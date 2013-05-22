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
          {"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2","width":599,"height":417,"extension":"png"}
          """

    Scenario Outline: Fetch different formats of the image based on the Accept header
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "<accept>"
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"

        Examples:
            | accept    | content-type |
            | image/gif | image/gif    |
            | image/jpeg| image/jpeg   |
            | image/png | image/png    |

    Scenario: Fetch image when not accepting images
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "application/json"
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2"
        Then I should get a response with "406 Not acceptable"
        And the "Content-Type" response header is "application/json"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"
        And the response body matches:
          """
          /{"error":{"code":406,"message":"Not acceptable","date":"[^"]+","imboErrorCode":0},"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2"}/
          """

    Scenario: Fetch image information using HTTP HEAD
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "image/png"
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2" using HTTP "HEAD"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/png"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"
        And the response body matches:
          """
          //
          """

    Scenario: Fetch image information using HTTP HEAD when not accepting images
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "application/json"
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2" using HTTP "HEAD"
        Then I should get a response with "406 Not acceptable"
        And the "Content-Type" response header is "application/json"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"
        And the response body matches:
          """
          //
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
          {"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2","width":599,"height":417,"extension":"png"}
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
        Then I should get a response with "404 Image not found"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Image not found" and the error code is "0"

    Scenario: Add a broken image
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And I attach "tests/Imbo/Fixtures/broken-image.jpg" to the request body
        When I request "/users/publickey/images/72e38ded1b41eda0c1701e6ff270eaf8" using HTTP "PUT"
        Then I should get a response with "415 Broken image"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Broken image" and the error code is "204"
