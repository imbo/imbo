Feature: Imbo features access control backed by a MongoDB database
    In order to get content from Imbo
    As an HTTP Client
    I must specify a public key in the URI or request headers

    Background:
        Given Imbo uses the "access-control-mutable.php" configuration
        And I prime the database with "access-control-mutable.php"

    Scenario: Request an access-controlled resource under an unknown user
        When I request "/users/user1337.json"
        Then I should get a response with "400 Permission denied (public key)"

    Scenario: Request an access-controlled resource with no public key specified
        When I request "/users/foobar.json"
        Then I should get a response with "400 Permission denied (public key)"

    Scenario: Request an access-controlled resource with invalid public key specified
        Given I use "invalid" and "foobar" for public and private keys
        And I include an access token in the query
        When I request "/users/user1.json"
        Then I should get a response with "400 Permission denied (public key)"
        And the Imbo error message is "Permission denied (public key)" and the error code is "0"

    Scenario Outline: Request an access-controlled resource with public key that does not have access to the user
        Given I use "foobar" and "barfoo" for public and private keys
        And I include an access token in the query
        When I request "<url>"
        Then I should get a response with "<status>"
        And the Imbo error message is "<message>" and the error code is "<code>"

        Examples:
            | url                  | status                             | code | message                        |
            | /users/user2         | 400 Permission denied (public key) | 0    | Permission denied (public key) |
            | /users/foobar/images | 400 Permission denied (public key) | 0    | Permission denied (public key) |

    Scenario: Request an access-controlled resource with valid public key specified
        Given I use "foobar" and "barfoo" for public and private keys
        And I include an access token in the query
        When I request "/users/barfoo/images.json"
        Then I should get a response with "200 OK"

    Scenario: Request an access-controlled resource with group that does not contain the resource
        Given I use "valid-group-pubkey" and "foobar" for public and private keys
        And I include an access token in the query
        When I request "/users/user/images/9c554794f784778cd436064faa2ea24a"
        Then I should get a response with "400 Permission denied (public key)"

    Scenario: Request user information when access is granted through a group
        Given I use "group-based" and "foobar" for public and private keys
        And I include an access token in the query
        When I request "/users/user1"
        Then I should get a response with "200 OK"

    Scenario: Request user information when resource is granted but not for the given user
        Given I use "group-based" and "foobar" for public and private keys
        And I include an access token in the query
        When I request "/users/other-user"
        Then I should get a response with "400 Permission denied (public key)"

    Scenario: Request user information when access is granted through a wildcard
        Given I use "wildcarded" and "foobar" for public and private keys
        And I include an access token in the query
        When I request "/users/random-user"
        Then I should get a response with "200 OK"
