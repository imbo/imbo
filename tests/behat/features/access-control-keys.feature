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
        When I request "/keys/master-pubkey/access.<extension>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the response body matches:
        """
        <response>
        """
        Examples:
            | extension | content-type     | response |
            | json      | application/json | #^\[{"id":".*?","resources":\["keys\.put","keys\.delete","accessrule\.get","accessrule\.head","accessrule\.delete","accessrules\.get","accessrules\.head","accessrules\.post"],"users":\[]},{"id":".*?","group":"something","users":\["some-user"]}]$# |
            | xml       | application/xml  | #^<\?xml version="1\.0" encoding="UTF-8"\?>\s*<imbo>\s*<access>\s*<rule id=".*?">\s*<resources>\s*<resource>keys\.put</resource>\s*<resource>keys\.delete</resource>\s*<resource>accessrule\.get</resource>\s*<resource>accessrule\.head</resource>\s*<resource>accessrule\.delete</resource>\s*<resource>accessrules\.get</resource>\s*<resource>accessrules\.head</resource>\s*<resource>accessrules\.post</resource>\s*</resources>\s*</rule><rule id=".*?">\s*<group>something</group>\s*<users>\s*<user>some-user</user>\s*</users>\s*</rule>\s*</access>\s*</imbo>$#ms |

    Scenario: Create a public key
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And the request body contains:
          """
          {"privateKey":"the-private-key"}
          """
        And I sign the request
        When I request "/keys/the-public-key" using HTTP "PUT"
        Then I should get a response with "201 Created"

    Scenario: Update the private key for an existing public key
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And the request body contains:
          """
          {"privateKey":"new-private-key"}
          """
        And I sign the request
        When I request "/keys/master-pubkey" using HTTP "PUT"
        Then I should get a response with "200 OK"

    Scenario: Create new public key without having access to the keys resource
        Given I use "foobar" and "barfoo" for public and private keys
        And the request body contains:
          """
          {"privateKey":"moo"}
          """
        And I sign the request
        When I request "/keys/some-new-pubkey" using HTTP "PUT"
        Then I should get a response with "400 Permission denied (public key)"

    Scenario: Update list of access rules for a public key
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And the request body contains:
          """
          [{"resources":["images.get"],"users":["user1"]},{"group":"read-images","users":["user1", "user5"]}]
          """
        And I sign the request
        When I request "/keys/foobar/access" using HTTP "POST"
        Then I should get a response with "200 OK"

    Scenario: Get an access control rule
        And I use "master-pubkey" and "master-privkey" for public and private keys
        And I include an access token in the query
        When I request "/keys/foobar/access/100000000000000000001337" using HTTP "GET"
        Then I should get a response with "200 OK"

    Scenario: Delete an access control rule
        And I use "master-pubkey" and "master-privkey" for public and private keys
        And I sign the request
        When I request "/keys/foobar/access/100000000000000000001337" using HTTP "DELETE"
        Then I should get a response with "200 OK"

    Scenario: Delete a public key
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I sign the request
        When I request "/keys/foobar" using HTTP "DELETE"
        Then I should get a response with "200 OK"

    Scenario Outline: The keys resource supports PUT and DELETE only
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I authenticate using "<auth-method>"
        And the request body contains:
        """
        <body>
        """
        When I request "/keys/foobar" using HTTP "<method>"
        Then I should get a response with "<status>"

        Examples:
            | method | auth-method  | body                             | status                 |
            | GET    | access-token |                                  | 405 Method not allowed |
            | HEAD   | access-token |                                  | 405 Method not allowed |
            | POST   | signature    |                                  | 405 Method not allowed |
            | PUT    | signature    | {"privateKey":"the-private-key"} | 200 OK                 |
            | DELETE | signature    |                                  | 200 OK                 |

    Scenario Outline: The access rules resource supports GET, HEAD and POST only
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I authenticate using "<auth-method>"
        And the request body contains:
        """
        <body>
        """
        When I request "/keys/master-pubkey/access" using HTTP "<method>"
        Then I should get a response with "<status>"

        Examples:
            | method | auth-method  | body                                             | status                 |
            | GET    | access-token |                                                  | 200 OK                 |
            | HEAD   | access-token |                                                  | 200 OK                 |
            | POST   | signature    | [{"resources":["images.get"],"users":["user1"]}] | 200 OK                 |
            | PUT    | signature    |                                                  | 405 Method not allowed |
            | DELETE | signature    |                                                  | 405 Method not allowed |

    Scenario Outline: The access rule resource supports GET, HEAD and POST only
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I authenticate using "<auth-method>"
        When I request "/keys/foobar/access/100000000000000000001337" using HTTP "<method>"
        Then I should get a response with "<status>"

        Examples:
            | method | auth-method  | body                          | status                 |
            | GET    | access-token |                               | 200 OK                 |
            | HEAD   | access-token |                               | 200 OK                 |
            | POST   | signature    | [{"resources":[],"users":[]}] | 405 Method not allowed |
            | PUT    | signature    |                               | 405 Method not allowed |
            | DELETE | signature    |                               | 200 OK                 |

    Scenario Outline: Operations on an immutable access control provider
        Given Imbo uses the "access-control.php" configuration
        And I use "valid-pubkey" and "foobar" for public and private keys
        And I authenticate using "<auth-method>"
        And the request body contains:
        """
        <body>
        """
        When I request "<uri>" using HTTP "<method>"
        Then I should get a response with "<status>"

        Examples:
            | uri                         | method | auth-method  | body                          | status                                  |
            | /keys/valid-pubkey          | GET    | access-token |                               | 405 Method not allowed                  |
            | /keys/valid-pubkey          | HEAD   | access-token |                               | 405 Method not allowed                  |
            | /keys/valid-pubkey          | POST   | signature    | {"privateKey": "secret"}      | 405 Method not allowed                  |
            | /keys/valid-pubkey          | PUT    | signature    | {"privateKey": "secret"}      | 405 Access control adapter is immutable |
            | /keys/valid-pubkey          | DELETE | signature    |                               | 405 Access control adapter is immutable |
            | /keys/valid-pubkey/access   | GET    | access-token |                               | 200 OK                                  |
            | /keys/valid-pubkey/access   | HEAD   | access-token |                               | 200 OK                                  |
            | /keys/valid-pubkey/access   | POST   | signature    | [{"resources":[],"users":[]}] | 405 Access control adapter is immutable |
            | /keys/valid-pubkey/access   | PUT    | signature    | [{"resources":[],"users":[]}] | 405 Method not allowed                  |
            | /keys/valid-pubkey/access   | DELETE | signature    |                               | 405 Method not allowed                  |
            | /keys/valid-pubkey/access/1 | GET    | access-token |                               | 200 OK                                  |
            | /keys/valid-pubkey/access/1 | HEAD   | access-token |                               | 200 OK                                  |
            | /keys/valid-pubkey/access/1 | POST   | signature    |                               | 405 Method not allowed                  |
            | /keys/valid-pubkey/access/1 | PUT    | signature    |                               | 405 Method not allowed                  |
            | /keys/valid-pubkey/access/1 | DELETE | signature    |                               | 405 Access control adapter is immutable |
