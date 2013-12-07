Feature: Imbo can apply watermarks to images
    In order to apply a watermark to an image
    As an HTTP Client
    I can use the watermark transformation

    Background:
        Given "tests/Imbo/Fixtures/image1.png" exists in Imbo with identifier "fc7d2d06993047a0b5056e8fac4462a2"

    Scenario: Apply a non-existing watermark
        Given I use "publickey" and "privatekey" for public and private keys
        And I specify "watermark:img=foobar" as transformation
        And I include an access token in the query
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2.png"
        Then I should get a response with "400 Watermark image not found"
