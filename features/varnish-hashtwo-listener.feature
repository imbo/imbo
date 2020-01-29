Feature: Imbo provides an event listener for the hashtwo Varnish module
    In order to make Varnish use the hashtwo module with Imbo images
    As a Varnish backend
    I must send correct headers

    Background:
        Given "tests/Fixtures/image1.png" exists for user "user"
        And I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        And Imbo uses the "varnish-hashtwo.php" configuration

    Scenario Outline: The hashtwo header is the same for all image variations
        Given I specify "<transformation>" as transformation
        When I request the previously added image
        Then the response status line is "200 OK"
        And the "X-HashTwo" response header matches "/imbo;image;user;[A-Za-z0-9_-]{12}, imbo;user;user/"

        Examples:
            | transformation   |
            | border           |
            | canvas           |
            | desaturate       |
            | flipHorizontally |
            | thumbnail        |
            | transpose        |

    Scenario Outline: A custom hashtwo header can be specified in the configuration
        Given I specify "<transformation>" as transformation
        When I request the previously added image
        Then the response status line is "200 OK"
        And the "X-Imbo-HashTwo" response header matches "/imbo;image;user;[A-Za-z0-9_-]{12}, imbo;user;user/"

        Examples:
            | transformation   |
            | border           |
            | canvas           |
            | desaturate       |
            | flipHorizontally |
            | thumbnail        |
            | transpose        |
