Feature: Imbo adds ETag's to some responses
    In order to improve client side performance
    As an image server
    I can specify ETag's in some responses

    Background:
        Given "tests/phpunit/Fixtures/image.png" is used as the test image for the "etags" feature
        And I use "publickey" and "privatekey" for public and private keys

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
        Given I include an access token in the query
        When I request "/users/user"
        Then I should get a response with "200 OK"
        And the "ETag" response header matches ""[a-z0-9]{32}""

    Scenario: Images resource includes an Etag
        Given I include an access token in the query
        When I request "/users/user/images"
        Then I should get a response with "200 OK"
        And the "ETag" response header matches ""[a-z0-9]{32}""

    Scenario: Different image formats result in different ETag's
        Given I include an access token in the query
        When I request the test image as a "png"
        And I request the test image as a "jpg"
        And I request the test image as a "gif"
        Then the "etag" response header is not the same for any of the requests

    Scenario: Metadata resource includes an ETag
        Given I include an access token in the query
        When I request the metadata of the test image
        Then I should get a response with "200 OK"
        And the "ETag" response header matches ""[a-z0-9]{32}""

    Scenario: Responses that is not 200 OK does not get ETags
        When I request "/users/user"
        Then I should get a response with "400 Missing access token"
        And the "ETag" response header does not exist
