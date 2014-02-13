Feature: Imbo provides an event listener for the hashtwo Varnish module
    In order to make Varnish use the hashtwo module with Imbo images
    As a Varnish backend
    I must send correct headers

    Background:
        Given "tests/Fixtures/image1.png" exists in Imbo

    Scenario Outline: The hashtwo header is the same for all image variations
        Given I use "publickey" and "privatekey" for public and private keys
        And I specify "<transformation>" as transformation
        And I include an access token in the query
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2.png"
        Then I should get a response with "200 OK"
        And the "X-HashTwo" response header is "imbo;image;publickey;fc7d2d06993047a0b5056e8fac4462a2, imbo;user;publickey"

        Examples:
            | transformation   |
            | border           |
            | canvas           |
            | desaturate       |
            | flipHorizontally |
            | thumbnail        |
            | transpose        |

    Scenario Outline: A custom hashtwo header can be specified in the configuration
        Given I use "publickey" and "privatekey" for public and private keys
        And I specify "<transformation>" as transformation
        And I include an access token in the query
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2.png"
        Then I should get a response with "200 OK"
        And the "X-Imbo-HashTwo" response header is "imbo;image;publickey;fc7d2d06993047a0b5056e8fac4462a2, imbo;user;publickey"

        Examples:
            | transformation   |
            | border           |
            | canvas           |
            | desaturate       |
            | flipHorizontally |
            | thumbnail        |
            | transpose        |
