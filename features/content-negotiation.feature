Feature: Imbo supports content negotiation
    In order to get the different content types
    As an HTTP Client
    I can specify the type I want in the Accept request header

    Background:
        Given "tests/Fixtures/image1.png" exists for user "user"
        And "tests/Fixtures/image.jpg" exists for user "user"
        And "tests/Fixtures/image.gif" exists for user "user"

    Scenario Outline: Imbo's resources can respond with different content types using content negotiation
        Given the "Accept" request header is "<accept>"
        And I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "<resource>"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "<content-type>"

        Examples:
            | resource                                                 | accept                                                          | content-type     |
            | /status                                                  | application/json                                                | application/json |
            | /status                                                  | image/*,*/*;q=0.1                                               | application/json |
            | /users/user                                              | application/json                                                | application/json |
            | /users/user                                              | image/*,*/*;q=0.1                                               | application/json |
            | /users/user/images                                       | application/json                                                | application/json |
            | /users/user/images                                       | image/*,*/*;q=0.1                                               | application/json |

    Scenario Outline: Imbo's metadata resource can respond with different content types using content negotiation
        Given the "Accept" request header is "<accept>"
        And I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request the metadata of the previously added image
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "<content-type>"

        Examples:
            | accept                                                          | content-type     |
            | application/json                                                | application/json |
            | image/*,*/*;q=0.1                                               | application/json |

    Scenario: If the client includes an extension, the Accept header should be ignored
        Given the "Accept" request header is "application/xml"
        When I request "/status.json"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"

    Scenario: If the server responds with an error, and the client included a valid extension, that type should be returned
        Given the "Accept" request header is "application/xml"
        When I request "/users/foobar.json"
        Then the response status line is "400 Permission denied (public key)"
        And the "Content-Type" response header is "application/json"

    Scenario Outline: Imbo uses the Accept header when encountering errors to choose the error format
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        And the "Accept" request header is "<accept>"
        When I request "/users/user/images/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa<extension>"
        Then the response status line is "<status-line>"
        And the "Content-Type" response header is "application/json"

        Examples:
            | accept    | extension | status-line         |
            | */*       | .png      | 404 Image not found |
            | image/png | .png      | 406 Not acceptable  |
            | */*       |           | 404 Image not found |
            | image/png |           | 406 Not acceptable  |

    Scenario: Fetch an image when not accepting images
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        And the "Accept" request header is "application/json"
        When I request the image resource for "tests/Fixtures/image1.png"
        Then the response status line is "406 Not acceptable"
        And the "Content-Type" response header is "application/json"
        And the "X-Imbo-OriginalExtension" response header is "png"
        And the "X-Imbo-OriginalFilesize" response header is "95576"
        And the "X-Imbo-OriginalHeight" response header is "417"
        And the "X-Imbo-OriginalMimeType" response header is "image/png"
        And the "X-Imbo-OriginalWidth" response header is "599"
        And the response body contains JSON:
          """
          {
            "error": {
              "code": 406,
              "message": "Not acceptable",
              "date": "@isDate()",
              "imboErrorCode": 0
            },
            "imageIdentifier": "@regExp(/[a-z0-9]+/)"
          }
          """

    Scenario Outline: Imbo uses the original mime type of the image if the client has no preferences
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        And the "Accept" request header is "image/*"
        When I request the image resource for "<image-path>"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "<content-type>"

        Examples:
            | image-path                | content-type |
            | tests/Fixtures/image1.png | image/png    |
            | tests/Fixtures/image.jpg  | image/jpeg   |
            | tests/Fixtures/image.gif  | image/gif    |

    Scenario Outline: Imbo uses the original mime type of the image if configuration has disabled content negotiation for images
        Given Imbo uses the "image-content-negotiation-disabled.php" configuration
        And I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        And the "Accept" request header is "<requested-content-type>"
        When I request the image resource for "<image-path>"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "<original-content-type>"

        Examples:
            | image-path                | requested-content-type | original-content-type |
            | tests/Fixtures/image1.png | image/gif              | image/png             |
            | tests/Fixtures/image.jpg  | image/png              | image/jpeg            |
            | tests/Fixtures/image.gif  | image/jpeg             | image/gif             |
