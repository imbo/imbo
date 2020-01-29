Feature: Imbo adds ETag's to some responses
    In order to improve client side performance
    As an image server
    I can specify ETag's in some responses

    Background:
        Given "tests/Fixtures/image.png" exists for user "user"
        And I use "publicKey" and "privateKey" for public and private keys

    Scenario: Index resource does not contain any Etag header
        When I request "/"
        Then the response status line is "200 Hell Yeah"
        And the "ETag" response header does not exist

    Scenario: Stats resource does not contain any Etag header
        When I request "/stats?statsAllow=*"
        Then the response status line is "200 OK"
        And the "ETag" response header does not exist

    Scenario: Status resource does not contain any Etag header
        When I request "/status"
        Then the response status line is "200 OK"
        And the "ETag" response header does not exist

    Scenario: User resource includes an Etag
        Given I include an access token in the query string
        When I request "/users/user"
        Then the response status line is "200 OK"
        And the "ETag" response header matches "/[a-z0-9]{32}/"

    Scenario: Images resource includes an Etag
        Given I include an access token in the query string
        When I request "/users/user/images"
        Then the response status line is "200 OK"
        And the "ETag" response header matches "/[a-z0-9]{32}/"

    Scenario: Different image formats result in different ETag's
        Given I include an access token in the query string for all requests
        When I request the previously added image as a "png"
        And I request the previously added image as a "jpg"
        And I request the previously added image as a "gif"
        Then the last 3 "etag" response headers are not the same

    Scenario: Metadata resource includes an ETag
        Given I include an access token in the query string
        When I request the metadata of the previously added image
        Then the "ETag" response header matches "/[a-z0-9]{32}/"

    Scenario: Responses that is not 200 OK does not get ETags
        When I request "/users/user"
        Then the response status line is "400 Missing access token"
        And the "ETag" response header does not exist
