Feature: Imbo requires an access token for read operations
    In order to get content from Imbo
    As an HTTP Client
    I must specify an access token in the URI

    Background:
        Given "tests/Fixtures/image.png" exists for user "user"

    Scenario: Request user information using the correct private key
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user"
        Then the response status line is "200 OK"

    Scenario: Request user information using the wrong private key
        Given I use "publicKey" and "foobar" for public and private keys
        And I include an access token in the query string
        When I request "/users/user"
        Then the response status line is "400 Incorrect access token"
        And the Imbo error message is "Incorrect access token" and the error code is "0"

    Scenario: Request user information using a correct read-only private key
        Given I use "ro-pubkey" and "read-only-key" for public and private keys
        And I include an access token in the query string
        And Imbo uses the "ro-rw-auth.php" configuration
        When I request "/users/someuser"
        Then the response status line is "200 OK"

    Scenario: Request user information without a valid access token
        When I request "/users/user?publicKey=publicKey"
        Then the response status line is "400 Missing access token"
        And the Imbo error message is "Missing access token" and the error code is "0"

    Scenario: Request image using no access token
        Given the "Accept" request header is "*/*"
        When I request "/users/user/images?publicKey=publicKey"
        Then the response status line is "400 Missing access token"

    Scenario: Can request a whitelisted transformation without access tokens
        Given I use "publicKey" and "privateKey" for public and private keys
        And the "Accept" request header is "*/*"
        And Imbo uses the "access-token-whitelist-transformation.php" configuration
        And I specify "whitelisted" as transformation
        When I request the previously added image
        Then the response status line is "200 OK"
        Then the image dimension is "100x50"

    Scenario: Can not issue transformations that are not whitelisted without a valid access token
        Given the "Accept" request header is "*/*"
        When I request "/users/user/images/929db9c5fc3099f7576f5655207eba47?publicKey=publicKey&t[]=thumbnail"
        Then the response status line is "400 Missing access token"

    Scenario: Request user information using the correct private key and a superfluous public key query parameter
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user?publicKey=publicKeyy"
        Then the response status line is "200 OK"

    Scenario: Request user information for a user with an incorrect public key specified as query parameter
        Given I use "rw-pubkey" and "read-only-key" for public and private keys
        And I include an access token in the query string
        And Imbo uses the "ro-rw-auth.php" configuration
        When I request "/users/someuser?publicKey=rw-pubkey"
        Then the response status line is "400 Incorrect access token"
        And the Imbo error message is "Incorrect access token" and the error code is "0"
