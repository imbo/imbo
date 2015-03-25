Feature: Imbo provides a way to access control resources on a per-public key basis
    In order to get content from Imbo
    As an HTTP Client
    I must specify a public key in the URI or request headers

    Scenario: Request an access-controlled resource under an unknown user
        Given Imbo uses the "access-control.php" configuration
        When I request "/users/user1337.json"
        Then I should get a response with "404 User not found"

    Scenario: Request an access-controlled resource with no public key specified
        Given Imbo uses the "access-control.php" configuration
        When I request "/users/user1.json"
        Then I should get a response with "400 Permission denied (public key)"

    Scenario: Request an access-controlled resource with invalid public key specified
        Given I use "invalid" and "foobar" for public and private keys
        And I include an access token in the query
        And Imbo uses the "access-control.php" configuration
        When I request "/users/user1.json"
        Then I should get a response with "400 Permission denied (public key)"
        And the Imbo error message is "Permission denied (public key)" and the error code is "0"

    Scenario: Request an access-controlled resource with public key that does not have access to the user
        Given I use "valid-pubkey" and "foobar" for public and private keys
        And I include an access token in the query
        And Imbo uses the "access-control.php" configuration
        When I request "/users/user2.json"
        Then I should get a response with "400 Permission denied (public key)"
        And the Imbo error message is "Permission denied (public key)" and the error code is "0"

    Scenario: Request an access-controlled resource with valid public key specified
        Given I use "valid-pubkey" and "foobar" for public and private keys
        And I include an access token in the query
        And Imbo uses the "access-control.php" configuration
        When I request "/users/user1.json"
        Then I should get a response with "200 OK"

    Scenario: Request an access-controlled resource with a public key that uses a resource group
        Given I use "valid-group-pubkey" and "foobar" for public and private keys
        And I include an access token in the query
        And Imbo uses the "access-control.php" configuration
        When I request "/users/user/images.json"
        Then I should get a response with "200 OK"

    Scenario: Request an access-controlled resource with group that does not contain the resource
        Given I use "valid-group-pubkey" and "foobar" for public and private keys
        And I include an access token in the query
        And Imbo uses the "access-control.php" configuration
        When I request "/users/user/images/9c554794f784778cd436064faa2ea24a"
        Then I should get a response with "400 Permission denied (public key)"

    Scenario: Request custom access-controlled resource with insufficient privileges
        Given I use "public" and "private" for public and private keys
        And I include an access token in the query
        And Imbo uses the "access-control.php" configuration
        When I request "/foobar"
        Then I should get a response with "400 Permission denied (public key)"

    Scenario: Request custom access-controlled resource that some other public key has access to
        Given I use "valid-pubkey" and "foobar" for public and private keys
        And I include an access token in the query
        And Imbo uses the "access-control.php" configuration
        When I request "/foobar"
        Then I should get a response with "400 Permission denied (public key)"

    Scenario: Request custom access-controlled resource with sufficient privileges specified using wildcard
        Given I use "valid-pubkey-with-wildcard" and "foobar" for public and private keys
        And I include an access token in the query
        And Imbo uses the "access-control.php" configuration
        When I request "/foobar"
        Then I should get a response with "200 OK"

    Scenario: Request user information when Imbo uses an alternative access control adapter
        Given I use "public" and "private" for public and private keys
        And I include an access token in the query
        And Imbo uses the "custom-access-control.php" configuration
        When I request "/users/public"
        Then I should get a response with "200 OK"
