Feature: Imbo can generate short URLs for images on demand
    In order to create short URLs
    As an HTTP Client
    I can request the short URLs resource

    Background:
        Given "tests/phpunit/Fixtures/image.png" exists in Imbo

    Scenario: Generate a short URL
        Given I use "user" and "key" for public and private keys
        And I sign the request
        And the request body contains:
          """
          {"imageIdentifier": "929db9c5fc3099f7576f5655207eba47", "user": "user", "extension": "gif", "query": "t[]=thumbnail:width=45,height=55&t[]=desaturate"}
          """
        When I request "/users/user/images/929db9c5fc3099f7576f5655207eba47/shorturls.json" using HTTP "POST"
        Then I should get a response with "201 Created"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
           """
           #^{"id":"[a-zA-Z0-9]{7}"}$#
           """

    Scenario Outline: Request an image using the short URL
        Given I generate a short URL with the following parameters:
            """
            <params>
            """

        When I request the image using the generated short URL
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<mime>"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "41423"
        And the "X-Imbo-Originalwidth" response header is "665"
        And the "X-Imbo-Originalheight" response header is "463"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the width of the image is "<width>"
        And the height of the image is "<height>"

        Examples:
            | params                                                                                                                                                       | mime       | width | height |
            | {"imageIdentifier": "929db9c5fc3099f7576f5655207eba47", "user": "publickey"}                                                                                 | image/png  | 665   | 463    |
            | {"imageIdentifier": "929db9c5fc3099f7576f5655207eba47", "user": "publickey", "extension": "gif"}                                                             | image/gif  | 665   | 463    |
            | {"imageIdentifier": "929db9c5fc3099f7576f5655207eba47", "user": "publickey", "query": "t[]=thumbnail"}                                                       | image/png  | 50    | 50     |
            | {"imageIdentifier": "929db9c5fc3099f7576f5655207eba47", "user": "publickey", "query": "t[]=thumbnail:width=45,height=55&t[]=desaturate"}                     | image/png  | 45    | 55     |
            | {"imageIdentifier": "929db9c5fc3099f7576f5655207eba47", "user": "publickey", "extension": "jpg", "query": "t[]=thumbnail:width=45,height=55&t[]=desaturate"} | image/jpeg | 45    | 55     |
