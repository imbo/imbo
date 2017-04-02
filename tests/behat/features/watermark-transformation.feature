Feature: Imbo can apply watermarks to images
    In order to apply a watermark to an image
    As an HTTP Client
    I can use the watermark transformation

    Background:
        Given "tests/phpunit/Fixtures/image.png" exists for user "user"
        And "tests/phpunit/Fixtures/colors.png" exists for user "user"
        And I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string

    Scenario: Apply a non-existing watermark
        Given I specify "watermark:img=foobar" as transformation
        When I request the image resource for "tests/phpunit/Fixtures/image.png"
        Then the response status line is "400 Watermark image not found"

    Scenario Outline: Apply an existing watermark
        Given I use "tests/phpunit/Fixtures/colors.png" as the watermark image with "<parameters>" as parameters
        When I request the image resource for "tests/phpunit/Fixtures/image.png"
        Then the response status line is "200 OK"
        And the image dimension is "665x463"
        And the pixel at coordinate "<coordinates>" has a color of "<color>"

        Examples:
            | parameters                                        | coordinates | color   |
            |                                                   | 0,0         | #000000 |
            | position=center                                   | 337,226     | #00ffff |
            | x=10,y=5,position=bottom-right,width=20,height=20 | 659,453     | #ff0000 |
