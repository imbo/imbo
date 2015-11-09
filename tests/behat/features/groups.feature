Feature: Imbo provides a groups endpoint
    In order to query resource groups
    As an HTTP Client
    I want to make requests against the groups endpoint

    Background:
        Given Imbo uses the "access-control.php" configuration

    Scenario Outline: Fetch list of groups
        Given I use "valid-group-pubkey" and "foobar" for public and private keys
        And I include an access token in the query
        When I request "/groups.<extension>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the response body matches:
        """
        <response>
        """
        Examples:
            | extension | content-type     | response |
            | json      | application/json | #^{"search":{"hits":2,"page":1,"limit":20,"count":2},"groups":\[{"name":"images-read","resources":\["images\.get","images\.head"]},{"name":"groups-read","resources":\["group\.get","group\.head","groups\.get","groups\.head"]}]}$# |
            | xml       | application/xml  | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<search>\s*<hits>2</hits>\s*<page>1</page>\s*<limit>20</limit>\s*<count>2</count>\s*</search>\s*<groups>\s*<group>\s*<name>images-read</name>\s*<resources>\s*<resource>images\.get</resource>\s*<resource>images\.head</resource>\s*</resources>\s*</group>\s*<group>\s*<name>groups-read</name>\s*<resources>\s*<resource>group\.get</resource>\s*<resource>group\.head</resource>\s*<resource>groups\.get</resource>\s*<resource>groups\.head</resource>\s*</resources>\s*</group>\s*</groups>\s*</imbo>$#ms |

    Scenario Outline: Fetch a list of groups with limit + paging
        Given I use "valid-group-pubkey" and "foobar" for public and private keys
        And I include an access token in the query
        When I request "/groups.json?limit=1&page=<page>"
        Then I should get a response with "200 OK"
        And the response body is:
        """
        <response>
        """
        Examples:
            | page | response |
            | 1    | {"search":{"hits":2,"page":1,"limit":1,"count":1},"groups":[{"name":"images-read","resources":["images.get","images.head"]}]} |
            | 2    | {"search":{"hits":2,"page":2,"limit":1,"count":1},"groups":[{"name":"groups-read","resources":["group.get","group.head","groups.get","groups.head"]}]} |

    Scenario: Fetch list of groups from MongoDB access control adapter
        Given Imbo uses the "access-control-mutable.php" configuration
        And I prime the database with "access-control-mutable.php"
        And I use "acl-creator" and "someprivkey" for public and private keys
        And I include an access token in the query
        When I request "/groups.json?limit=2"
        Then I should get a response with "200 OK"
        And the response body is:
        """
        {"search":{"hits":3,"page":1,"limit":2,"count":2},"groups":[{"name":"existing-group","resources":["group.get","group.head"]},{"name":"user-stats","resources":["user.get","user.head"]}]}
        """

    Scenario: Fetch list of groups without specifying a public key
        Given I do not specify a public and private key
        When I request "/groups.json"
        Then I should get a response with "400 Permission denied (public key)"
