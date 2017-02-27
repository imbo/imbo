Feature: Imbo can apply border to images
    In order to apply a border to an image
    As an HTTP Client
    I can use the border transformation

    Scenario: Apply an outbound border to only top/bottom
        Given "tests/phpunit/Fixtures/transparency.png" exists for user "user"
        And I include an access token in the query using "publicKey" and "privateKey"
        And I specify "border:color=bf1942,height=100,width=0,mode=outbound" as transformation
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the image dimension is "512x712"
        And the image width is 512
        And the image height is 712
        And the pixel at coordinate "0,0" has a color of "#bf1942"
        And the pixel at coordinate "64,164" has a color of "#225d10"
        And the pixel at coordinate "0,164" has a color of "#225d10"
        And the pixel at coordinate "64,662" has a color of "#bf1942"
        And the pixel at coordinate "448,292" has a color of "#588e00"
        And the pixel at coordinate "448,292" has an alpha of "1"
        And the pixel at coordinate "192,164" has an alpha of "0"

    Scenario: Apply an outbound border to an image without an alpha channel
        Given "tests/phpunit/Fixtures/512x512.png" exists for user "user"
        And I include an access token in the query using "publicKey" and "privateKey"
        And I specify "border:color=bf1942,height=100,width=0,mode=outbound" as transformation
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the image width is "512"
        And the image height is "712"
        And the pixel at coordinate "0,0" has a color of "#bf1942"
        And the pixel at coordinate "64,164" has a color of "#225d10"
        And the pixel at coordinate "0,164" has a color of "#225d10"
        And the pixel at coordinate "64,662" has a color of "#bf1942"
        And the pixel at coordinate "448,292" has a color of "#588e00"

    Scenario: Apply an inline border
        Given "tests/phpunit/Fixtures/transparency.png" exists for user "user"
        And I include an access token in the query using "publicKey" and "privateKey"
        And I specify "border:color=bf1942,height=100,width=100,mode=inline" as transformation
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the image width is "512"
        And the image height is "512"
        And the pixel at coordinate "0,0" has a color of "#bf1942"
        And the pixel at coordinate "114,114" has a color of "#225d10"
        And the pixel at coordinate "340,115" has a color of "#95c400"
        And the pixel at coordinate "100,411" has a color of "#588e00"
        And the pixel at coordinate "382,411" has a color of "#95c400"
        And the pixel at coordinate "413,413" has a color of "#bf1942"
