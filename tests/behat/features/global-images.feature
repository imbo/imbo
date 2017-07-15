Feature: Imbo provides a global images endpoint
    In order to query images
    As an HTTP Client
    I want to make requests against the images endpoint

    Background:
        Given "tests/phpunit/Fixtures/image1.png" exists for user "user"
        And "tests/phpunit/Fixtures/image.jpg" exists for user "user"
        And "tests/phpunit/Fixtures/image.gif" exists for user "other-user"
        And "tests/phpunit/Fixtures/1024x256.png" exists for user "other-user"

    Scenario: Fetch images without specifying any users (all images)
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/images.json"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
              "search":
              {
                "hits": 4,
                "page": 1,
                "limit": 20,
                "count": 4
              },
              "images": "@arrayLength(4)",
              "images[0]":
              {
                "added": "@isDate()",
                "updated": "@isDate()",
                "checksum": "@regExp(/^[a-z0-9]{32}$/)",
                "originalChecksum": "@regExp(/^[a-z0-9]{32}$/)",
                "extension": "@regExp(/^(jpg|png|gif)$/)",
                "size": "@variableType(int)",
                "width": "@variableType(int)",
                "height": "@variableType(int)",
                "mime": "@regExp(#^image/(jpeg|gif|png)$#)",
                "imageIdentifier": "@regExp(/^[a-zA-Z0-9-_]{12}$/)",
                "user": "@regExp(/^(other-)?user$/)"
              },
              "images[1]": {
                "added": "@isDate()",
                "updated": "@isDate()",
                "checksum": "@regExp(/^[a-z0-9]{32}$/)",
                "originalChecksum": "@regExp(/^[a-z0-9]{32}$/)",
                "extension": "@regExp(/^(jpg|png|gif)$/)",
                "size": "@variableType(int)",
                "width": "@variableType(int)",
                "height": "@variableType(int)",
                "mime": "@regExp(#^image/(jpeg|gif|png)$#)",
                "imageIdentifier": "@regExp(/^[a-zA-Z0-9-_]{12}$/)",
                "user": "@regExp(/^(other-)?user$/)"
              },
              "images[2]": {
                "added": "@isDate()",
                "updated": "@isDate()",
                "checksum": "@regExp(/^[a-z0-9]{32}$/)",
                "originalChecksum": "@regExp(/^[a-z0-9]{32}$/)",
                "extension": "@regExp(/^(jpg|png|gif)$/)",
                "size": "@variableType(int)",
                "width": "@variableType(int)",
                "height": "@variableType(int)",
                "mime": "@regExp(#^image/(jpeg|gif|png)$#)",
                "imageIdentifier": "@regExp(/^[a-zA-Z0-9-_]{12}$/)",
                "user": "@regExp(/^(other-)?user$/)"
              },
              "images[3]": {
                "added": "@isDate()",
                "updated": "@isDate()",
                "checksum": "@regExp(/^[a-z0-9]{32}$/)",
                "originalChecksum": "@regExp(/^[a-z0-9]{32}$/)",
                "extension": "@regExp(/^(jpg|png|gif)$/)",
                "size": "@variableType(int)",
                "width": "@variableType(int)",
                "height": "@variableType(int)",
                "mime": "@regExp(#^image/(jpeg|gif|png)$#)",
                "imageIdentifier": "@regExp(/^[a-zA-Z0-9-_]{12}$/)",
                "user": "@regExp(/^(other-)?user$/)"
              }
            }
            """

    Scenario: Fetch images for user with wildcard access
        Given I use "wildcard" and "*" for public and private keys
        And I include an access token in the query string
        When I request "/images.json?users[]=user&users[]=other-user"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
              "search":
              {
                "hits": 4,
                "page": 1,
                "limit": 20,
                "count": 4
              },
              "images": "@arrayLength(4)",
              "images[0]":
              {
                "added": "@isDate()",
                "updated": "@isDate()",
                "checksum": "@regExp(/^[a-z0-9]{32}$/)",
                "originalChecksum": "@regExp(/^[a-z0-9]{32}$/)",
                "extension": "@regExp(/^(jpg|png|gif)$/)",
                "size": "@variableType(int)",
                "width": "@variableType(int)",
                "height": "@variableType(int)",
                "mime": "@regExp(#^image/(jpeg|gif|png)$#)",
                "imageIdentifier": "@regExp(/^[a-zA-Z0-9-_]{12}$/)",
                "user": "@regExp(/^(other-)?user$/)"
              },
              "images[1]": {
                "added": "@isDate()",
                "updated": "@isDate()",
                "checksum": "@regExp(/^[a-z0-9]{32}$/)",
                "originalChecksum": "@regExp(/^[a-z0-9]{32}$/)",
                "extension": "@regExp(/^(jpg|png|gif)$/)",
                "size": "@variableType(int)",
                "width": "@variableType(int)",
                "height": "@variableType(int)",
                "mime": "@regExp(#^image/(jpeg|gif|png)$#)",
                "imageIdentifier": "@regExp(/^[a-zA-Z0-9-_]{12}$/)",
                "user": "@regExp(/^(other-)?user$/)"
              },
              "images[2]": {
                "added": "@isDate()",
                "updated": "@isDate()",
                "checksum": "@regExp(/^[a-z0-9]{32}$/)",
                "originalChecksum": "@regExp(/^[a-z0-9]{32}$/)",
                "extension": "@regExp(/^(jpg|png|gif)$/)",
                "size": "@variableType(int)",
                "width": "@variableType(int)",
                "height": "@variableType(int)",
                "mime": "@regExp(#^image/(jpeg|gif|png)$#)",
                "imageIdentifier": "@regExp(/^[a-zA-Z0-9-_]{12}$/)",
                "user": "@regExp(/^(other-)?user$/)"
              },
              "images[3]": {
                "added": "@isDate()",
                "updated": "@isDate()",
                "checksum": "@regExp(/^[a-z0-9]{32}$/)",
                "originalChecksum": "@regExp(/^[a-z0-9]{32}$/)",
                "extension": "@regExp(/^(jpg|png|gif)$/)",
                "size": "@variableType(int)",
                "width": "@variableType(int)",
                "height": "@variableType(int)",
                "mime": "@regExp(#^image/(jpeg|gif|png)$#)",
                "imageIdentifier": "@regExp(/^[a-zA-Z0-9-_]{12}$/)",
                "user": "@regExp(/^(other-)?user$/)"
              }
            }
            """

    Scenario Outline: Fetch images specifying users
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/images.json<query-string>"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            <response>
            """

        Examples:
            | query-string                     | response                                                                         |
            | ?users[]=user&users[]=other-user | {"search":{"hits":4,"page":1,"limit":20,"count":4}, "images": "@arrayLength(4)"} |
            | ?users[]=user                    | {"search":{"hits":2,"page":1,"limit":20,"count":2}, "images": "@arrayLength(2)"} |
            | ?users[]=other-user              | {"search":{"hits":2,"page":1,"limit":20,"count":2}, "images": "@arrayLength(2)"} |

    Scenario: Fetch images specifying users that the publickey does not have access to
        Given I use "unpriviledged" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/images.json?users[]=foo&users[]=bar"
        Then the response status line is "400 Public key does not have access to the users: [foo, bar]"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
              "error":
              {
                "code": 400,
                "message": "Public key does not have access to the users: [foo, bar]",
                "date": "@isDate()",
                "imboErrorCode": 0
              }
            }
            """

    Scenario: Fetch all images when the publickey does not have access to specific users
        Given I use "unpriviledged" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/images.json"
        Then the response status line is "400 Public key does not have access to the users: [other-user]"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
              "error":
              {
                "code": 400,
                "message": "Public key does not have access to the users: [other-user]",
                "date": "@isDate()",
                "imboErrorCode": 0
              }
            }
            """
