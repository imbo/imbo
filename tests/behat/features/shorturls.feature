Feature: Imbo can generate short URLs for images on demand
    In order to create short URLs
    As an HTTP Client
    I can request the short URLs resource

    Background:
        Given "tests/phpunit/Fixtures/image.png" exists for user "user"

    Scenario: Responds with 404 when the image does not exist
        Given I use "publicKey" and "privateKey" for public and private keys
        And I sign the request
        And the request body is:
            """
            {"user": "user", "imageIdentifier": "id", "extension": "gif", "query": null}
            """
        When I request "/users/user/images/id/shorturls" using HTTP "POST"
        Then the response status line is "404 Image does not exist"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
              "error":
              {
                "code": 404,
                "message": "Image does not exist",
                "date": "@isDate()",
                "imboErrorCode": 0
              },
              "imageIdentifier": "id"
            }
            """

    Scenario: Generate a short URL
        Given I use "publicKey" and "privateKey" for public and private keys
        And I sign the request
        And I generate a short URL for "tests/phpunit/Fixtures/image.png" with the following parameters:
            """
            {"user": "user", "extension": "gif"}
            """
        Then the response status line is "201 Created"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
           """
           {"id":"@regExp(/^[a-zA-Z0-9]{7}$/)"}
           """

    Scenario: Generate a short URL without having access to the user
        Given "tests/phpunit/Fixtures/image.png" exists for user "other-user"
        And I use "unpriviledged" and "privatekey" for public and private keys
        And I sign the request
        And I generate a short URL for "tests/phpunit/Fixtures/image.png" with the following parameters:
            """
            {"user": "other-user", "extension": "gif"}
            """
        Then the response status line is "400 Permission denied (public key)"

    Scenario Outline: Request an image using the short URL
        Given I use "publicKey" and "privateKey" for public and private keys
        And I sign the request
        And I generate a short URL for "tests/phpunit/Fixtures/image.png" with the following parameters:
            """
            <params>
            """

        When I request the image using the generated short URL
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "<mime>"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "41423"
        And the "X-Imbo-Originalwidth" response header is "665"
        And the "X-Imbo-Originalheight" response header is "463"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the image dimension is "<width>x<height>"

        Examples:
            | params                                                                                           | mime       | width | height |
            | {"user": "user"}                                                                                 | image/png  | 665   | 463    |
            | {"user": "user", "extension": "gif"}                                                             | image/gif  | 665   | 463    |
            | {"user": "user", "query": "t[]=thumbnail"}                                                       | image/png  | 50    | 50     |
            | {"user": "user", "query": "t[]=thumbnail:width=45,height=55&t[]=desaturate"}                     | image/png  | 45    | 55     |
            | {"user": "user", "extension": "jpg", "query": "t[]=thumbnail:width=45,height=55&t[]=desaturate"} | image/jpeg | 45    | 55     |
