Feature: Imbo provides a metadata endpoint
    In order to handle metadata
    As an HTTP Client
    I want to make requests against the metadata endpoint

    Background:
        Given "tests/phpunit/Fixtures/image1.png" is used as the test image

    Scenario Outline: Get metadata when image has no metadata attached
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request the metadata of the test image as "<extension>"
        Then I should get a response with "200 OK"
        And the response body matches:
           """
           <response>
           """

        Examples:
            | extension | response |
            | json      | #^{}$# |
            | xml       | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<metadata></metadata>\s*</imbo>$#ms |

    Scenario: Attach metadata to an image
        Given I use "publickey" and "privatekey" for public and private keys
        And the request body contains:
          """
          {"foo": "bar"}
          """
        And I sign the request
        When I request the metadata of the test image using HTTP "PUT"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {"foo":"bar"}
           """

    Scenario Outline: Get metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request the metadata of the test image as "<extension>"
        Then I should get a response with "200 OK"
        And the response body matches:
           """
           <response>
           """

        Examples:
            | extension | response |
            | json      | #^{"foo":"bar"}$# |
            | xml       | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<metadata><tag key="foo">bar</tag></metadata>\s*</imbo>$#sm |

    Scenario: Partially update metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And the request body contains:
          """
          {"bar":"foo"}
          """
        And I sign the request
        When I request the metadata of the test image using HTTP "POST"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {"foo":"bar","bar":"foo"}
           """

    Scenario Outline: Get updated metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request the metadata of the test image as "<extension>"
        Then I should get a response with "200 OK"
        And the response body matches:
           """
           <response>
           """

        Examples:
            | extension | response |
            | json      | #^{"foo":"bar","bar":"foo"}$# |
            | xml       | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<metadata><tag key="foo">bar</tag><tag key="bar">foo</tag></metadata>\s*</imbo>$#sm |

    Scenario: Replace metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And the request body contains:
          """
          {"key": "value"}
          """
        And I sign the request
        When I request the metadata of the test image using HTTP "PUT"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {"key":"value"}
           """

    Scenario Outline: Get replaced metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request the metadata of the test image as "<extension>"
        Then I should get a response with "200 OK"
        And the response body matches:
           """
           <response>
           """

        Examples:
            | extension | response |
            | json      | #^{"key":"value"}$# |
            | xml       | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<metadata><tag key="key">value</tag></metadata>\s*</imbo>$#sm |

    Scenario: Delete metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        When I request the metadata of the test image using HTTP "DELETE"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {}
           """

    Scenario Outline: Get deleted metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request the metadata of the test image as "<extension>"
        Then I should get a response with "200 OK"
        And the response body matches:
           """
           <response>
           """

        Examples:
            | extension | response |
            | json      | #^{}$# |
            | xml       | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<metadata></metadata>\s*</imbo>$#ms |
