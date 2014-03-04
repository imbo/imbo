Feature: Imbo adds ETag's to some responses
    In order to improve client side performance
    As an image server
    I can specify ETag's in some responses

    Background:
        Given "tests/phpunit/Fixtures/image.png" exists in Imbo

    Scenario: Index resource does not contain any Etag header
        When I request "/"
        Then I should get a response with "200 Hell Yeah"
        And the "ETag" response header does not exist

    Scenario: Stats resource does not contain any Etag header
        When I request "/stats?statsAllow=*"
        Then I should get a response with "200 OK"
        And the "ETag" response header does not exist

    Scenario: Status resource does not contain any Etag header
        When I request "/status"
        Then I should get a response with "200 OK"
        And the "ETag" response header does not exist

    Scenario: User resource includes an Etag
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey"
        Then I should get a response with "200 OK"
        And the "ETag" response header matches ""[a-z0-9]{32}""

    Scenario: Images resource includes an Etag
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images"
        Then I should get a response with "200 OK"
        And the "ETag" response header matches ""[a-z0-9]{32}""

    Scenario Outline: Different image formats result in different ETag's
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "<acceptHeader>"
        When I request "/users/publickey/images/929db9c5fc3099f7576f5655207eba47"
        Then I should get a response with "200 OK"
        And the "ETag" response header is "<etag>"

        Examples:
            | acceptHeader | etag  |
            | */*          | "929db9c5fc3099f7576f5655207eba47" |
            | image/*      | "929db9c5fc3099f7576f5655207eba47" |
            | image/png    | "929db9c5fc3099f7576f5655207eba47" |
            | image/jpeg   | "1500190f1aca23117c53490e856e209c" |
            | image/gif    | "44a80402ab32b74593721053541dfb9f" |

    Scenario: Metadata resource includes an ETag
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images/929db9c5fc3099f7576f5655207eba47/metadata"
        Then I should get a response with "200 OK"
        And the "ETag" response header matches ""[a-z0-9]{32}""

    Scenario: Short URL resource includes an ETag
        Given I fetch the short URL of "/users/publickey/images/929db9c5fc3099f7576f5655207eba47.gif?t[]=thumbnail&t[]=border"
        And the "ETag" response header is ""c33ca397c520edd1827f2c8f59d95190""
        When I request the image using the short URL
        Then I should get a response with "200 OK"
        And the "ETag" response header is ""c33ca397c520edd1827f2c8f59d95190""

    Scenario: Responses that is not 200 OK does not get ETags
        Given I use "publickey" and "privatekey" for public and private keys
        When I request "/users/publickey"
        Then I should get a response with "400 Missing access token"
        And the "ETag" response header does not exist
