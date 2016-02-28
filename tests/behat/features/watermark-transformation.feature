Feature: Imbo can apply watermarks to images
    In order to apply a watermark to an image
    As an HTTP Client
    I can use the watermark transformation

    Background:
        Given "tests/phpunit/Fixtures/image.png" is used as the test image for the "watermark" feature

    Scenario: Apply a non-existing watermark
        Given I use "publickey" and "privatekey" for public and private keys
        And I specify "watermark:img=foobar" as transformation
        And I include an access token in the query
        When I request the test image
        Then I should get a response with "400 Watermark image not found"

    Scenario Outline: Apply an existing watermark
        Given I use "publickey" and "privatekey" for public and private keys
        And I use "tests/phpunit/Fixtures/colors.png" as the watermark image with "<parameters>" as parameters
        And I include an access token in the query
        When I request the test image as a "png"
        Then I should get a response with "200 OK"
        And the width of the image is "665"
        And the height of the image is "463"
        And the pixel at coordinate "<coordinates>" should have a color of "<color>"

        Examples:
            | parameters                                        | coordinates | color   |
            |                                                   | 0, 0        | #000000 |
            | position=center                                   | 337, 226    | #00ffff |
            | x=10,y=5,position=bottom-right,width=20,height=20 | 659, 453    | #ff0000 |
