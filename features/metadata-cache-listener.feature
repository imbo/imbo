Feature: Imbo enables caching of metadata
    In order to speed up reading of metadata
    As an image server
    I will cache and re-use fetched metadata

    Background:
        Given "tests/Fixtures/image1.png" exists in Imbo

    Scenario: Attach metadata to an image
        Given I use "publickey" and "privatekey" for public and private keys
        And the request body contains:
          """
          {"foo": "bar"}
          """
        And I sign the request
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta" using HTTP "PUT"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {"foo":"bar"}
           """

    Scenario: Fetch metadata for image when metadata is not cached
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta"
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
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the "X-Imbo-MetadataCache" response header is "Hit"
        And the response body is:
           """
          {"foo":"bar"}
           """

    Scenario: Fetch metadata as XML representation
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta.xml"
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
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta" using HTTP "DELETE"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {}
           """

    Scenario: Fetch metadata for image when metadata was just deleted
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta"
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
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta.xml"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/xml"
        And the "X-Imbo-MetadataCache" response header is "Hit"
        And the response body matches:
           """
           #<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<metadata></metadata>\s*</imbo>#ms
           """
