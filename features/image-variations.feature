Feature: Imbo provides an event listener that generates variations when adding images
    In order to speed up scaling transformations
    As a developer
    I can add the event listener to the Imbo configuration file

    Background:
        Given Imbo uses the "image-variations.php" configuration
        And "tests/Fixtures/1024x256.png" exists for user "user"
        And I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string

    Scenario: Request an image with no transformations
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the "X-Imbo-ImageVariation" response header does not exist

    Scenario: Request an image with no scaling transformations
        Given I specify "desaturate" as transformation
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the "X-Imbo-ImageVariation" response header does not exist

    Scenario: Request an image with a resize transformation which upscales the original
        Given I specify "resize:width=2048" as transformation
        When I request the previously added image as a "png"
        Then the "X-Imbo-ImageVariation" response header does not exist
        And the image width is "2048"

    Scenario: Request an image with a maxSize transformation which downscales the original
        Given I specify "maxSize:width=320" as transformation
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the "X-Imbo-ImageVariation" response header is "320x80"
        And the image width is "320"

    Scenario: Request an image with a maxSize transformation which only slightly downscales the original
        Given I specify "maxSize:width=1020" as transformation
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the "X-Imbo-ImageVariation" response header does not exist
        And the image width is "1020"

    Scenario: Request an image with a thumbnail transformation using inset mode and no width
        Given I specify "thumbnail:height=128,fit=inset" as transformation
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the "X-Imbo-ImageVariation" response header is "128x32"
        And the image width is "50"

    Scenario: Request an image with a maxSize and crop transformation
        Given I specify the following transformations:
          """
          crop:width=256,height=256,x=768,y=0
          maxSize:width=100
          """
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the "X-Imbo-ImageVariation" response header is "512x128"
        And the pixel at coordinate "5,5" has a color of "#215d10"
        And the image dimension is "100x100"

    Scenario: Request an image with crop in the middle of the chain
        Given I specify the following transformations:
          """
          rotate:angle=90,bg=000000
          crop:width=256,height=256,x=0,y=768
          maxSize:width=100
          """
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the "X-Imbo-ImageVariation" response header is "512x128"
        And the pixel at coordinate "5,5" has a color of "#215d10"
        And the image dimension is "100x100"
