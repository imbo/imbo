Feature: Imbo provides a way to access control resources on a per-public key basis
    In order to get content from Imbo
    As an HTTP Client
    I must specify a public key in the URI or request headers

    Scenario: Request a resource under an unknown user
        Given Imbo uses the "access-control.php" configuration
        When I request "/users/user1337.json"
        And the response status line is "400 Permission denied (public key)"

    Scenario: Request a resource with no public key specified
        Given Imbo uses the "access-control.php" configuration
        When I request "/users/user1.json"
        Then the response status line is "400 Permission denied (public key)"

    Scenario: Request a resource with invalid public key specified
        Given I use "invalid" and "foobar" for public and private keys
        And I include an access token in the query string
        And Imbo uses the "access-control.php" configuration
        When I request "/users/user1.json"
        Then the response status line is "400 Permission denied (public key)"
        And the Imbo error message is "Permission denied (public key)" and the error code is "0"

    Scenario: Request a resource with public key that does not have access to the user
        Given I use "valid-pubkey" and "foobar" for public and private keys
        And I include an access token in the query string
        And Imbo uses the "access-control.php" configuration
        When I request "/users/user2.json"
        Then the response status line is "400 Permission denied (public key)"
        And the Imbo error message is "Permission denied (public key)" and the error code is "0"

    Scenario: Request a resource with valid public key specified
        Given I use "valid-pubkey" and "foobar" for public and private keys
        And I include an access token in the query string
        And Imbo uses the "access-control.php" configuration
        When I request "/users/user1.json"
        Then the response status line is "200 OK"

    Scenario: Request a resource with a public key that has access to all users and the resource
        Given I use "valid-pubkey-with-wildcard" and "foobar" for public and private keys
        And I include an access token in the query string
        And Imbo uses the "access-control.php" configuration
        When I request "/users/some-user.json"
        Then the response status line is "200 OK"

    Scenario: Request a resource with a public key that has access to all users but not requested resource
        Given I use "valid-pubkey-with-wildcard" and "foobar" for public and private keys
        And I include an access token in the query string
        And Imbo uses the "access-control.php" configuration
        When I request "/users/some-user/images.json"
        Then the response status line is "400 Permission denied (public key)"
        And the Imbo error message is "Permission denied (public key)" and the error code is "0"

    Scenario: Request a resource with a public key that uses a resource group
        Given I use "valid-group-pubkey" and "foobar" for public and private keys
        And I include an access token in the query string
        And Imbo uses the "access-control.php" configuration
        When I request "/users/user/images.json"
        Then the response status line is "200 OK"

    Scenario: Request a resource with group that does not contain the resource
        Given I use "valid-group-pubkey" and "foobar" for public and private keys
        And I include an access token in the query string
        And Imbo uses the "access-control.php" configuration
        When I request "/users/user/images/9c554794f784778cd436064faa2ea24a"
        Then the response status line is "400 Permission denied (public key)"

    Scenario: Request custom access-controlled resource with insufficient privileges
        Given I use "public" and "private" for public and private keys
        And I include an access token in the query string
        And Imbo uses the "access-control.php" configuration
        When I request "/foobar"
        Then the response status line is "400 Permission denied (public key)"

    Scenario: Request custom access-controlled resource that a different public key has access to
        Given I use "valid-group-pubkey" and "private" for public and private keys
        And I include an access token in the query string
        And Imbo uses the "access-control.php" configuration
        When I request "/foobar"
        Then the response status line is "400 Permission denied (public key)"

    Scenario: Request custom access-controlled resource with sufficient privileges specified using wildcard
        Given I use "valid-pubkey-with-wildcard" and "private" for public and private keys
        And I include an access token in the query string
        And Imbo uses the "access-control.php" configuration
        When I request "/foobar"
        Then the response status line is "200 OK"
        And the response body is:
            """
            {"foo":[1,2,3]}
            """

    Scenario: Request user information when Imbo uses an alternative access control adapter
        Given I use "public" and "private" for public and private keys
        And I include an access token in the query string
        And Imbo uses the "custom-access-control.php" configuration
        When I request "/users/public"
        Then the response status line is "200 OK"

    Scenario Outline: Request open resources with default configuration
        Given the "Accept" request header is "application/json"
        When I request "<url>"
        Then the response status line is "<status>"
        And the response body contains JSON:
          """
          <response>
          """

        Examples:
            | url          | status           | response |
            | /            | 200 Hell Yeah    | {"site": "http://imbo.io"} |
            | /status.json | 200 OK           | {"date": "@isDate()", "database": true, "storage": true} |
