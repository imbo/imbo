Feature: Imbo supports content negotiation
    In order to get the different content types
    As an HTTP Client
    I can specify the type I want in the Accept request header

    Background:
        Given "tests/phpunit/Fixtures/image1.png" exists in Imbo
        And "tests/phpunit/Fixtures/image.jpg" exists in Imbo
        And "tests/phpunit/Fixtures/image.gif" exists in Imbo

    Scenario Outline: Imbo's resources can respond with different content types using content negotiation
        Given the "Accept" request header is "<accept>"
        And I include an access token in the query
        When I request "<resource>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"

        Examples:
            | resource                                                 | accept                                                          | content-type     |
            | /status                                                  | application/json                                                | application/json |
            | /status                                                  | application/xml                                                 | application/xml  |
            | /status                                                  | text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 | application/xml  |
            | /status                                                  | image/*,*/*;q=0.1                                               | application/json |
            | /users/user                                              | application/json                                                | application/json |
            | /users/user                                              | application/xml                                                 | application/xml  |
            | /users/user                                              | text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 | application/xml  |
            | /users/user                                              | image/*,*/*;q=0.1                                               | application/json |
            | /users/user/images                                       | application/json                                                | application/json |
            | /users/user/images                                       | application/xml                                                 | application/xml  |
            | /users/user/images                                       | text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 | application/xml  |
            | /users/user/images                                       | image/*,*/*;q=0.1                                               | application/json |

    Scenario Outline: Imbo's metadata resource can respond with different content types using content negotiation
        Given the "Accept" request header is "<accept>"
        And I include an access token in the query
        When I request the metadata of the previously added image
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"

        Examples:
            | accept                                                          | content-type     |
            | application/json                                                | application/json |
            | application/xml                                                 | application/xml  |
            | text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 | application/xml  |
            | image/*,*/*;q=0.1                                               | application/json |

    Scenario: If the client includes an extension, the Accept header should be ignored
        Given the "Accept" request header is "application/xml"
        When I request "/status.json"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"

    Scenario: If the server responds with an error, and the client included a valid extension, that type should be returned
        Given the "Accept" request header is "application/xml"
        When I request "/users/foobar.json"
        Then I should get a response with "400 Permission denied (public key)"
        And the "Content-Type" response header is "application/json"

    Scenario Outline: Imbo uses the Accept header when encountering errors to choose the error format
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "<accept>"
        When I request "/users/user/images/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa<extension>"
        Then I should get a response with "<reason>"
        And the "Content-Type" response header is "application/json"

        Examples:
            | accept    | extension | reason              |
            | */*       | .png      | 404 Image not found |
            | image/png | .png      | 406 Not acceptable  |
            | */*       |           | 404 Image not found |
            | image/png |           | 406 Not acceptable  |

    Scenario: Fetch an image when not accepting images
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "application/json"
        When I request the image resource for "tests/phpunit/Fixtures/image1.png"
        Then I should get a response with "406 Not acceptable"
        And the "Content-Type" response header is "application/json"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"
        And the response body matches:
          """
          /{"error":{"code":406,"message":"Not acceptable","date":"[^"]+","imboErrorCode":0},"imageIdentifier":"[^"]+"}/
          """

    Scenario Outline: Imbo uses the original mime type of the image if the client has no preferences
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "image/*"
        When I request the image resource for "<image-path>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"

        Examples:
            | image-path                        | content-type |
            | tests/phpunit/Fixtures/image1.png | image/png    |
            | tests/phpunit/Fixtures/image.jpg  | image/jpeg   |
            | tests/phpunit/Fixtures/image.gif  | image/gif    |

    Scenario Outline: Imbo uses the original mime type of the image if configuration has disabled content negotiation for images
        Given I use "publickey" and "privatekey" for public and private keys
        And Imbo uses the "image-content-negotiation-disabled.php" configuration
        And I include an access token in the query
        And the "Accept" request header is "<requested-content-type>"
        When I request the image resource for "<image-path>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<original-content-type>"

        Examples:
            | image-path                        | requested-content-type | original-content-type |
            | tests/phpunit/Fixtures/image1.png | image/gif              | image/png             |
            | tests/phpunit/Fixtures/image.jpg  | image/png              | image/jpeg            |
            | tests/phpunit/Fixtures/image.gif  | image/jpeg             | image/gif             |
