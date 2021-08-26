Feature: Imbo can crop images using smart size and POIs
    In order to crop an image based on A POI
    As an HTTP Client
    I can use the smart size transformation

    Background:
        Given "features/fixtures/smartsize.png" exists for user "user" with the following metadata:
            """
            {"poi": [{"cx": 410, "cy": 310}]}
            """
        And I use "publicKey" and "privateKey" for public and private keys

    Scenario Outline: Smart size image
        Given I include an access token in the query string
        And I specify "<transformation>" as transformation
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the image dimension is "<width>x<height>"
        And the pixel at coordinate "<coord>" has a color of "<color>"
        And the "X-Imbo-POIs-Used" response header is "1"

        Examples:
            | transformation                                         | coord   | color   | width | height |
            | smartSize:width=250,height=250,poi=410,310,crop=close  | 125,125 | #00ff00 | 250   | 250    |
            | smartSize:width=250,height=250,poi=410,310,crop=close  | 110,110 | #ffffff | 250   | 250    |
            | smartSize:width=250,height=250,poi=410,310,crop=medium | 125,125 | #00ff00 | 250   | 250    |
            | smartSize:width=250,height=250,poi=410,310,crop=medium | 110,110 | #ffffff | 250   | 250    |
            | smartSize:width=250,height=250,poi=410,310,crop=wide   | 125,125 | #00ff00 | 250   | 250    |
            | smartSize:width=250,height=250,poi=410,310,crop=wide   | 110,110 | #ffffff | 250   | 250    |
            | smartSize:width=600,height=250,poi=410,310,crop=close  | 300,125 | #00ff00 | 600   | 250    |
            | smartSize:width=600,height=250,poi=410,310,crop=close  | 315,140 | #ffffff | 600   | 250    |
            | smartSize:width=600,height=250,poi=410,310,crop=medium | 300,125 | #00ff00 | 600   | 250    |
            | smartSize:width=600,height=250,poi=410,310,crop=medium | 315,140 | #ffffff | 600   | 250    |
            | smartSize:width=600,height=250,poi=410,310,crop=wide   | 257,125 | #00ff00 | 600   | 250    |
            | smartSize:width=600,height=250,poi=410,310,crop=wide   | 237,105 | #ffffff | 600   | 250    |
            | smartSize:width=250,height=600,poi=410,310,crop=close  | 125,300 | #00ff00 | 250   | 600    |
            | smartSize:width=250,height=600,poi=410,310,crop=close  | 110,315 | #ffffff | 250   | 600    |
            | smartSize:width=250,height=600,poi=410,310,crop=medium | 125,247 | #00ff00 | 250   | 600    |
            | smartSize:width=250,height=600,poi=410,310,crop=medium | 110,267 | #ffffff | 250   | 600    |
            | smartSize:width=250,height=600,poi=410,310,crop=wide   | 125,240 | #00ff00 | 250   | 600    |
            | smartSize:width=250,height=600,poi=410,310,crop=wide   | 110,255 | #ffffff | 250   | 600    |

    Scenario: Smart size based on POI stored in metadata
        Given I specify "smartSize:width=250,height=250" as transformation
        And I include an access token in the query string
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the image dimension is "250x250"
        And the pixel at coordinate "125,125" has a color of "#00ff00"
        And the "X-Imbo-POIs-Used" response header is "1"

    Scenario: Smart size based on POI without center coordinates stored in metadata
        Given the request body is:
            """
            {"poi": [{"x": 400, "y": 300, "width": 20, "height": 20}]}
            """
        And I sign the request
        And I request the metadata of the previously added image using HTTP "PUT"
        When I specify "smartSize:width=250,height=250" as transformation
        And I include an access token in the query string
        And I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the image dimension is "250x250"
        And the pixel at coordinate "125,125" has a color of "#00ff00"
        And the "X-Imbo-POIs-Used" response header is "1"

    Scenario Outline: Smart size falls back to simple crop/resize when no POI data is found
        Given the request body is:
            """
            {}
            """
        And I sign the request
        And I request the metadata of the previously added image using HTTP "PUT"
        When I specify "smartSize:width=<width>,height=<height>" as transformation
        And I include an access token in the query string
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the image dimension is "<width>x<height>"
        And the pixel at coordinate "<coord>" has a color of "#00ff00"
        And the "X-Imbo-POIs-Used" response header is "0"

        Examples:
            | coord  | width | height |
            | 92,101 | 250   | 250    |
            | 200,39 | 500   | 150    |
            | 7,201  | 150   | 500    |
