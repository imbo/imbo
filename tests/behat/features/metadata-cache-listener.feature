Feature: Imbo enables caching of metadata
    In order to speed up reading of metadata
    As an image server
    I will cache and re-use fetched metadata

    Background:
        Given "tests/phpunit/Fixtures/image1.png" is used as the test image for the "metadata cache" feature

    Scenario: Attach metadata to an image
        Given I use "publickey" and "privatekey" for public and private keys
        And the request body contains:
          """
          {"foo": "bar"}
          """
        And I sign the request
        And Imbo uses the "metadata-cache.php" configuration
        When I request the metadata of the test image using HTTP "PUT"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {"foo":"bar"}
           """

    Scenario: Fetch metadata for image when metadata is not cached
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And Imbo uses the "metadata-cache.php" configuration
        When I request the metadata of the test image
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the "X-Imbo-MetadataCache" response header is "Miss"
        And the response body is:
           """
          {"foo":"bar"}
           """

    Scenario: Fetch metadata for image when metadata is cached
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And Imbo uses the "metadata-cache.php" configuration
        When I request the metadata of the test image
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the "X-Imbo-MetadataCache" response header is "Hit"
        And the response body is:
           """
          {"foo":"bar"}
           """

    Scenario: Fetch metadata as XML representation, which should hit the same cached data
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And Imbo uses the "metadata-cache.php" configuration
        When I request the metadata of the test image as "xml"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/xml"
        And the "X-Imbo-MetadataCache" response header is "Hit"
        And the response body matches:
           """
           #<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<metadata><tag key="foo">bar</tag></metadata>\s*</imbo>#ms
           """

    Scenario: Delete metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And Imbo uses the "metadata-cache.php" configuration
        When I request the metadata of the test image using HTTP "DELETE"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {}
           """

    Scenario: Fetch metadata for image when metadata was just deleted
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And Imbo uses the "metadata-cache.php" configuration
        When I request the metadata of the test image
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the "X-Imbo-MetadataCache" response header is "Miss"
        And the response body is:
           """
          {}
           """

    Scenario: Fetch metadata in XML representation for image when metadata was just deleted
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And Imbo uses the "metadata-cache.php" configuration
        When I request the metadata of the test image as "xml"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/xml"
        And the "X-Imbo-MetadataCache" response header is "Hit"
        And the response body matches:
           """
           #<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<metadata></metadata>\s*</imbo>#ms
           """

