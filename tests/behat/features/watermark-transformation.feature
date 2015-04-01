Feature: Imbo can apply watermarks to images
    In order to apply a watermark to an image
    As an HTTP Client
    I can use the watermark transformation

    Background:
        Given "tests/phpunit/Fixtures/image1.png" exists in Imbo

    Scenario: Apply a non-existing watermark
        Given I use "publickey" and "privatekey" for public and private keys
        And I specify "watermark:img=foobar" as transformation
        And I include an access token in the query
        When I request "/users/user/images/fc7d2d06993047a0b5056e8fac4462a2.png"
        Then I should get a response with "400 Watermark image not found"

#            | watermark:img=929db9c5fc3099f7576f5655207eba47                                                    | 599   | 417    |
#            | watermark:img=929db9c5fc3099f7576f5655207eba47,position=center                                    | 599   | 417    |
#            | watermark:img=929db9c5fc3099f7576f5655207eba47,x=10,y=20,position=bottom-right,width=10,height=40 | 599   | 417    |