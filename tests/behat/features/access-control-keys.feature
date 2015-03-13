Feature: Imbo provides a keys endpoint
    In order to query public keys
    As an HTTP Client
    I want to make requests against the keys endpoint

    Background:
        Given Imbo uses the "access-control-mutable.php" configuration
        And I prime the database with "access-control-mutable.php"

    Scenario Outline: Fetch access control rules for a public key
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I include an access token in the query
        When I request "/keys/master-pubkey.<extension>/access"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the response body matches:
        """
        <response>
        """
        Examples:
            | extension | content-type     | response |
            | json      | application/json | #^\[{"id":".*?","resources":\["access\.get","access\.head"],"users":\[]},{"id":".*?","group":"something","users":\["some-user"]}]$# |
            | xml       | application/xml  | #^<\?xml version="1\.0" encoding="UTF-8"\?>\s*<imbo>\s*<access>\s*<rule id=".*?">\s*<resources>\s*<resource>access\.get</resource>\s*<resource>access\.head</resource>\s*</resources>\s*</rule><rule id=".*?">\s*<group>something</group>\s*<users>\s*<user>some-user</user>\s*</users>\s*</rule>\s*</access>\s*</imbo>$#ms |

    Scenario: Create a public key
        Given Imbo uses the "access-control-mutable.php" configuration
        And I use "master-pubkey" and "master-privkey" for public and private keys
        And the request body contains:
          """
          {"privateKey":"the-private-key"}
          """
        And I sign the request
        When I request "/keys/foobar" using HTTP "PUT"
        Then I should get a response with "201 Created"

    Scenario: Update the private key for an existing public key
        Given Imbo uses the "access-control-mutable.php" configuration
        And I use "master-pubkey" and "master-privkey" for public and private keys
        And the request body contains:
          """
          {"privateKey":"new-private-key"}
          """
        And I sign the request
        When I request "/keys/foobar" using HTTP "PUT"
        Then I should get a response with "200 OK"
        # Perhaps add a test to ensure new keys can't be added/updated without the pubkey used having access to the keys endpoint?

    Scenario: Update list of access rules for a public key
        Given Imbo uses the "access-control-mutable.php" configuration
        And I use "master-pubkey" and "master-privkey" for public and private keys
        And the request body contains:
          """
          [{"resources":["images.get"],"users":["user1"]},{"group":"read-images","users":["user1", "user5"]}]
          """
        And I sign the request
        When I request "/keys/foobar/access" using HTTP "POST"
        Then I should get a response with "200 OK"

    Scenario: Delete an access-control rule
        Given Imbo uses the "access-control-mutable.php" configuration
        And I use "master-pubkey" and "master-privkey" for public and private keys
        And I sign the request
        When I request "/keys/foobar/access/someId" using HTTP "DELETE"
        Then I should get a response with "200 OK"
        # How do we get the ID of a rule we can test? Prime the database?

  Scenario: Delete a public key
        Given Imbo uses the "access-control-mutable.php" configuration
        And I use "master-pubkey" and "master-privkey" for public and private keys
        And I sign the request
        When I request "/keys/foobar" using HTTP "DELETE"
        Then I should get a response with "200 OK"
