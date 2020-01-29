Feature: Imbo can adjust color levels of images
    In order to adjust color levels of an image
    As an HTTP Client
    I can use the watermark transformation

    Background:
        Given "tests/Fixtures/colors.png" exists for user "user"
        And I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string

    Scenario: Apply a transformation that increases level of red
        Given I specify "level:channel=r,amount=100" as transformation
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the pixel at coordinate "5,55" has a color of "#de3f3f"

    Scenario: Apply a transformation that increases level for all channels
        Given I specify "level:channel=rgb,amount=100" as transformation
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the pixel at coordinate "22,32" has a color of "#ffed00"
