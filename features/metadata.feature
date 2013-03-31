Feature: Imbo provides a metadata endpoint
    In order to handle metadata
    As an HTTP Client
    I want to make requests against the metadata endpoint

    Scenario: Add an image
        Given "tests/Imbo/Fixtures/image1.png" exists in Imbo with identifier "fc7d2d06993047a0b5056e8fac4462a2"

    Scenario Outline: Get metadata when image has no metadata attached
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "<accept>"
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the response body <match>:
           """
           <response-body>
           """

        Examples:
            | accept           | content-type     | match    | response-body         |
            | application/json | application/json | is       | {}                    |
            | application/xml  | application/xml  | contains | <metadata></metadata> |
            | text/html        | text/html        | contains | <p>No metadata</p>    |

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
           {"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2"}
           """

    Scenario Outline: Get metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "<accept>"
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the response body <match>:
           """
           <response-body>
           """

        Examples:
            | accept           | content-type     | match    | response-body                                 |
            | application/json | application/json | is       | {"foo":"bar"}                                 |
            | application/xml  | application/xml  | contains | <metadata><tag key="foo">bar</tag></metadata> |
            | text/html        | text/html        | contains | <dl><dt>foo</dt><dd>bar</dd></dl>             |

    Scenario: Partially update metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And the request body contains:
          """
          {"bar": "foo"}
          """
        And I sign the request
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta" using HTTP "POST"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2"}
           """

    Scenario Outline: Get updated metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "<accept>"
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the response body <match>:
           """
           <response-body>
           """

        Examples:
            | accept           | content-type     | match    | response-body                                                         |
            | application/json | application/json | is       | {"foo":"bar","bar":"foo"}                                             |
            | application/xml  | application/xml  | contains | <metadata><tag key="foo">bar</tag><tag key="bar">foo</tag></metadata> |
            | text/html        | text/html        | contains | <dl><dt>foo</dt><dd>bar</dd><dt>bar</dt><dd>foo</dd></dl>             |

    Scenario: Replace metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And the request body contains:
          """
          {"key": "value"}
          """
        And I sign the request
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta" using HTTP "PUT"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2"}
           """

    Scenario Outline: Get replaced metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "<accept>"
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the response body <match>:
           """
           <response-body>
           """

        Examples:
            | accept           | content-type     | match    | response-body                                   |
            | application/json | application/json | is       | {"key":"value"}                                 |
            | application/xml  | application/xml  | contains | <metadata><tag key="key">value</tag></metadata> |
            | text/html        | text/html        | contains | <dl><dt>key</dt><dd>value</dd></dl>             |

    Scenario: Delete metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta" using HTTP "DELETE"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body is:
           """
           {"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2"}
           """

    Scenario Outline: Get deleted metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "<accept>"
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2/meta"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the response body <match>:
           """
           <response-body>
           """

        Examples:
            | accept           | content-type     | match    | response-body         |
            | application/json | application/json | is       | {}                    |
            | application/xml  | application/xml  | contains | <metadata></metadata> |
            | text/html        | text/html        | contains | <p>No metadata</p>    |
