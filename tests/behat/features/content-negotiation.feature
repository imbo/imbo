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
            | resource                                                      | accept                                                          | content-type     |
            | /status                                                       | application/json                                                | application/json |
            | /status                                                       | application/xml                                                 | application/xml  |
            | /status                                                       | text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 | application/xml  |
            | /status                                                       | image/*,*/*;q=0.1                                               | application/json |
            | /users/publickey                                              | application/json                                                | application/json |
            | /users/publickey                                              | application/xml                                                 | application/xml  |
            | /users/publickey                                              | text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 | application/xml  |
            | /users/publickey                                              | image/*,*/*;q=0.1                                               | application/json |
            | /users/publickey/images                                       | application/json                                                | application/json |
            | /users/publickey/images                                       | application/xml                                                 | application/xml  |
            | /users/publickey/images                                       | text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 | application/xml  |
            | /users/publickey/images                                       | image/*,*/*;q=0.1                                               | application/json |
            | /users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta | application/json                                                | application/json |
            | /users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta | application/xml                                                 | application/xml  |
            | /users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta | text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 | application/xml  |
            | /users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta | image/*,*/*;q=0.1                                               | application/json |

    Scenario: If the client includes an extension, the Accept header should be ignored
        Given the "Accept" request header is "application/xml"
        When I request "/status.json"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"

    Scenario: If the server responds with an error, and the client included a valid extension, that type should be returned
        Given the "Accept" request header is "application/xml"
        When I request "/users/foobar.json"
        Then I should get a response with "404 User not found"
        And the "Content-Type" response header is "application/json"

    Scenario Outline: Imbo uses the Accept header when encountering errors to choose the error format
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "<accept>"
        When I request "/users/publickey/images/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa<extension>"
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

    Scenario Outline: Imbo uses the original mime type of the image if the client has no preferences
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "image/*"
        When I request "/users/publickey/images/<image-identifier>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"

        Examples:
            | image-identifier                 | content-type |
            | fc7d2d06993047a0b5056e8fac4462a2 | image/png    |
            | f3210f1bb34bfbfa432cc3560be40761 | image/jpeg   |
            | b5426b4c008e378c201526d2baaec599 | image/gif    |

    Scenario Outline: Imbo uses the original mime type of the image if configuration has disabled content negotiation for images
        Given I use "publickey" and "privatekey" for public and private keys
        And Imbo uses the "image-content-negotiation-disabled.php" configuration
        And I include an access token in the query
        And the "Accept" request header is "<requested-content-type>"
        When I request "/users/publickey/images/<image-identifier>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<original-content-type>"

        Examples:
            | image-identifier                 | requested-content-type | original-content-type |
            | fc7d2d06993047a0b5056e8fac4462a2 | image/gif              | image/png             |
            | f3210f1bb34bfbfa432cc3560be40761 | image/png              | image/jpeg            |
            | b5426b4c008e378c201526d2baaec599 | image/jpeg             | image/gif             |
