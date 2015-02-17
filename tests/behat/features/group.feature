Feature: Imbo provides a group endpoint
    In order to query resource groups
    As an HTTP Client
    I want to make requests against the group endpoint

    Background:
        Given Imbo uses the "access-control.php" configuration

    Scenario Outline: Fetch resources of a group
        Given I use "valid-group-pubkey" and "foobar" for public and private keys
        And I include an access token in the query
        When I request "/groups/groups-read.<extension>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the response body matches:
        """
        <response>
        """
        Examples:
            | extension | content-type     | response |
            | json      | application/json | #^{"resources":\["groups\.get","groups\.head"]}$# |
            | xml       | application/xml  | #^<\?xml version="1\.0" encoding="UTF-8"\?>\s*<imbo>\s*<resources>\s*<resource>groups\.get</resource>\s*<resource>groups\.head</resource>\s*</resources>\s*</imbo>$#ms |

