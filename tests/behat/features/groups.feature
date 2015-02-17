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
            | json      | application/json | #^{"groups":\[{"name":"images-read","resources":\["images\.get"]},{"name":"groups-read","resources":\["groups\.get","groups\.head"]}]}$# |
            | xml       | application/xml  | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<groups>\s*<group>\s*<name>images-read</name>\s*<resources>\s*<resource>images\.get</resource>\s*</resources>\s*</group>\s*<group>\s*<name>groups-read</name>\s*<resources>\s*<resource>groups\.get</resource>\s*<resource>groups\.head</resource>\s*</resources>\s*</group>\s*</groups>\s*</imbo>$#ms |

    Scenario: Fetch list of groups without specifying a public key
        Given I do not specify a public and private key
        When I request "/groups.json"
        Then I should get a response with "400 Permission denied (public key)"
