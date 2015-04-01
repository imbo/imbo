Feature: Imbo can adjust color levels of images
    In order to adjust color levels of an image
    As an HTTP Client
    I can use the watermark transformation

    Background:
        Given "tests/phpunit/Fixtures/colors.png" exists in Imbo

    Scenario: Apply a transformation that increases level of red
        Given I use "publickey" and "privatekey" for public and private keys
        And I specify "level:channel=r,amount=100" as transformation
        And I include an access token in the query
        When I request the previously added image as a "png"
        Then I should get a response with "200 OK"
        And the pixel at coordinate "5, 55" should have a color of "#de3f3f"

    Scenario: Apply a transformation that increases level for all channels
        Given I use "publickey" and "privatekey" for public and private keys
        And I specify "level:channel=rgb,amount=100" as transformation
        And I include an access token in the query
        When I request the previously added image as a "png"
        Then I should get a response with "200 OK"
        And the pixel at coordinate "22, 32" should have a color of "#ffed00"
