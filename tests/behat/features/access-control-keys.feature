Feature: Imbo provides a keys endpoint
    In order to query public keys
    As an HTTP Client
    I want to make requests against the keys endpoint

    Background:
        Given Imbo uses the "access-control-mutable.php" configuration
        And I prime the database with "access-control-mutable.php"

    Scenario: Fetch access control rules for a public key
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I include an access token in the query string
        When I request "/keys/master-pubkey/access.json"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            [
              {
                "id": "@regExp(/[a-z0-9]+/)",
                "resources":
                [
                  "keys.put",
                  "keys.head",
                  "keys.delete",
                  "accessrule.get",
                  "accessrule.head",
                  "accessrule.delete",
                  "accessrules.get",
                  "accessrules.head",
                  "accessrules.post"
                ],
                "users": "@arrayLength(0)"
              },
              {
                "id": "@regExp(/[a-z0-9]+/)",
                "group": "something",
                "users":
                [
                  "some-user"
                ]
              }
            ]
            """

    Scenario: Fetch access control rules for a public key that has access to all users
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I include an access token in the query string
        When I request "/keys/wildcarded/access.json"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            [
              {
                "id": "@regExp(/[a-z0-9]+/)",
                "group": "user-stats",
                "users": "*"
              }
            ]
            """

    Scenario: Fetch access control rules with expanded groups
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        Given I include an access token in the query string
        When I request "/keys/group-based/access.json?expandGroups=1"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            [
              {
                "id": "@regExp(/[a-z0-9]+/)",
                "group": "user-stats",
                "users":
                [
                  "user1"
                ],
                "resources":
                [
                  "user.get",
                  "user.head"
                ]
              }
            ]
            """

    Scenario: Create a public key
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I sign the request
        And the request body is:
            """
            {"privateKey":"the-private-key"}
            """
        When I request "/keys/the-public-key" using HTTP "PUT"
        Then the response status line is "201 Created"

    Scenario Outline: Check if a public key exist
        Given I use "acl-creator" and "someprivkey" for public and private keys
        Given I include an access token in the query string
        When I request "/keys/<pubkey>" using HTTP "HEAD"
        Then the response status line is "<status>"

        Examples:
            | pubkey       | status                   |
            | foobar       | 200 OK                   |
            | non-existant | 404 Public key not found |

    Scenario: Update the private key for an existing public key
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I sign the request
        And the request body is:
            """
            {"privateKey":"new-private-key"}
            """
        When I request "/keys/master-pubkey" using HTTP "PUT"
        Then the response status line is "200 OK"

    Scenario: Create new public key without having access to the keys resource
        Given I use "foobar" and "barfoo" for public and private keys
        And I sign the request
        And the request body is:
            """
            {"privateKey":"moo"}
            """
        When I request "/keys/some-new-pubkey" using HTTP "PUT"
        Then the response status line is "400 Permission denied (public key)"

    Scenario Outline: Add access rules for a public key
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I sign the request
        And the request body is:
            """
            <body>
            """
        When I request "/keys/foobar/access" using HTTP "POST"
        Then the response status line is "<status>"

        Examples:
            | body                                                                                                   | status                                                                 |
            |                                                                                                        | 400 No access rule data provided                                       |
            | {}                                                                                                     | 400 Neither group nor resources found in rule                          |
            | {"group":"existing-group"}                                                                             | 400 Users not specified in rule                                        |
            | {"group":"non-existant-group"}                                                                         | 400 Group 'non-existant-group' does not exist                          |
            | {"resources":"images.get"}                                                                             | 400 Illegal value in resources array. String array expected            |
            | {"resources":[123]}                                                                                    | 400 Illegal value in resources array. String array expected            |
            | {"resources":["images.get"]}                                                                           | 400 Users not specified in rule                                        |
            | {"resources":["images.get"],"users":"foobar"}                                                          | 400 Illegal value for users property. Allowed: '*' or array with users |
            | {"foo":"bar","bar":"foo"}                                                                              | 400 Found unknown properties in rule: [foo, bar]                       |
            | [{"resources":["images.get"],"users":["user1"]}]                                                       | 200 OK                                                                 |
            | {"group":"existing-group","users":["user1", "user5"]}                                                  | 200 OK                                                                 |

    Scenario: Get an access control rule
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I include an access token in the query string
        When I request "/keys/foobar/access/100000000000000000001337" using HTTP "GET"
        Then the response status line is "200 OK"

    @test
    Scenario: Delete an access control rule
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I sign the request
        When I request "/keys/foobar/access/100000000000000000001337" using HTTP "DELETE"
        Then the response status line is "200 OK"
        And the ACL rule under public key "foobar" with ID "100000000000000000001337" no longer exists

    @test
    Scenario: Delete a public key
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I sign the request
        When I request "/keys/foobar" using HTTP "DELETE"
        Then the response status line is "200 OK"
        And the "foobar" public key no longer exists

    Scenario Outline: The keys resource supports PUT, HEAD and DELETE only
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I authenticate using "<auth-method>"
        And the request body is:
            """
            <body>
            """
        When I request "/keys/foobar" using HTTP "<method>"
        Then the response status line is "<status>"

        Examples:
            | method | auth-method         | body                             | status                 |
            | GET    | access-token        |                                  | 405 Method not allowed |
            | HEAD   | access-token        |                                  | 200 OK                 |
            | POST   | signature           |                                  | 405 Method not allowed |
            | PUT    | signature           | {"privateKey":"the-private-key"} | 200 OK                 |
            | DELETE | signature           |                                  | 200 OK                 |
            | POST   | signature (headers) |                                  | 405 Method not allowed |
            | PUT    | signature (headers) | {"privateKey":"the-private-key"} | 200 OK                 |
            | DELETE | signature (headers) |                                  | 200 OK                 |

    Scenario Outline: The access rules resource supports GET, HEAD and POST only
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I authenticate using "<auth-method>"
        And the request body is:
            """
            <body>
            """
        When I request "/keys/master-pubkey/access" using HTTP "<method>"
        Then the response status line is "<status>"

        Examples:
            | method | auth-method         | body                                             | status                 |
            | GET    | access-token        |                                                  | 200 OK                 |
            | HEAD   | access-token        |                                                  | 200 OK                 |
            | POST   | signature           | [{"resources":["images.get"],"users":["user1"]}] | 200 OK                 |
            | PUT    | signature           |                                                  | 405 Method not allowed |
            | DELETE | signature           |                                                  | 405 Method not allowed |
            | POST   | signature (headers) | [{"resources":["images.get"],"users":["user1"]}] | 200 OK                 |
            | PUT    | signature (headers) |                                                  | 405 Method not allowed |
            | DELETE | signature (headers) |                                                  | 405 Method not allowed |

    Scenario Outline: The access rule resource supports GET, HEAD and POST only
        Given I use "master-pubkey" and "master-privkey" for public and private keys
        And I authenticate using "<auth-method>"
        When I request "/keys/foobar/access/100000000000000000001337" using HTTP "<method>"
        Then the response status line is "<status>"

        Examples:
            | method | auth-method         | body                          | status                 |
            | GET    | access-token        |                               | 200 OK                 |
            | HEAD   | access-token        |                               | 200 OK                 |
            | POST   | signature           | [{"resources":[],"users":[]}] | 405 Method not allowed |
            | PUT    | signature           |                               | 405 Method not allowed |
            | DELETE | signature           |                               | 200 OK                 |
            | POST   | signature (headers) | [{"resources":[],"users":[]}] | 405 Method not allowed |
            | PUT    | signature (headers) |                               | 405 Method not allowed |
            | DELETE | signature (headers) |                               | 200 OK                 |

    Scenario Outline: Operations on an immutable access control provider
        Given Imbo uses the "access-control.php" configuration
        And I use "valid-pubkey" and "foobar" for public and private keys
        And I authenticate using "<auth-method>"
        And the request body is:
            """
            <body>
            """
        When I request "<uri>" using HTTP "<method>"
        Then the response status line is "<status>"

        Examples:
            | uri                         | method | auth-method  | body                          | status                                  |
            | /keys/valid-pubkey          | GET    | access-token |                               | 405 Method not allowed                  |
            | /keys/valid-pubkey          | HEAD   | access-token |                               | 200 OK                                  |
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
