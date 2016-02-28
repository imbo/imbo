Feature: Imbo can apply border to images
    In order to apply a border to an image
    As an HTTP Client
    I can use the border transformation

    Background:
        Given "tests/phpunit/Fixtures/transparency.png" is used as the test image for the "border" feature

    Scenario: Apply an outbound border to only top/bottom
        Given I use "publickey" and "privatekey" for public and private keys
        And I specify "border:color=bf1942,height=100,width=0,mode=outbound" as transformation
        And I include an access token in the query
        When I request the test image as a "png"
        Then I should get a response with "200 OK"
        And the width of the image is "512"
        And the height of the image is "712"
        And the pixel at coordinate "0,0" should have a color of "#bf1942"
        And the pixel at coordinate "64,164" should have a color of "#225d10"
        And the pixel at coordinate "0,164" should have a color of "#225d10"
        And the pixel at coordinate "64,662" should have a color of "#bf1942"
        And the pixel at coordinate "448,292" should have a color of "#588e00"
        And the pixel at coordinate "448,292" should have an alpha of "1"
        And the pixel at coordinate "192,164" should have an alpha of "0"

    Scenario: Apply an outbound border to an image without an alpha channel
        Given "tests/phpunit/Fixtures/512x512.png" is used as the test image
        And I use "publickey" and "privatekey" for public and private keys
        And I specify "border:color=bf1942,height=100,width=0,mode=outbound" as transformation
        And I include an access token in the query
        When I request the test image as a "png"
        Then I should get a response with "200 OK"
        And the width of the image is "512"
        And the height of the image is "712"
        And the pixel at coordinate "0,0" should have a color of "#bf1942"
        And the pixel at coordinate "64,164" should have a color of "#225d10"
        And the pixel at coordinate "0,164" should have a color of "#225d10"
        And the pixel at coordinate "64,662" should have a color of "#bf1942"
        And the pixel at coordinate "448,292" should have a color of "#588e00"

    Scenario: Apply an inline border
        Given I use "publickey" and "privatekey" for public and private keys
        And I specify "border:color=bf1942,height=100,width=100,mode=inline" as transformation
        And I include an access token in the query
        When I request the test image as a "png"
        Then I should get a response with "200 OK"
        And the width of the image is "512"
        And the height of the image is "512"
        And the pixel at coordinate "0,0" should have a color of "#bf1942"
        And the pixel at coordinate "114,114" should have a color of "#225d10"
        And the pixel at coordinate "340,115" should have a color of "#95c400"
        And the pixel at coordinate "100,411" should have a color of "#588e00"
        And the pixel at coordinate "382,411" should have a color of "#95c400"
        And the pixel at coordinate "413,413" should have a color of "#bf1942"
