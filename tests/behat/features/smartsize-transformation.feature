Feature: Imbo can crop images using smart size and POIs
    In order to crop an image based on A POI
    As an HTTP Client
    I can use the smart size transformation

    Background:
        Given "tests/behat/fixtures/trolltunga.jpg" exists for user "user" with the following metadata:
            """
            {"poi": [{"cx": 810, "cy": 568}]}
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
            | transformation                                         | coord | color   | width | height |
            | smartSize:width=250,height=250,poi=810,568,crop=close  | 0,0   | #396473 | 250   | 250    |
            | smartSize:width=250,height=250,poi=810,568,crop=medium | 0,0   | #355170 | 250   | 250    |
            | smartSize:width=250,height=250,poi=810,568,crop=wide   | 0,0   | #52607c | 250   | 250    |
            | smartSize:width=600,height=250,poi=810,568,crop=close  | 0,0   | #50748c | 600   | 250    |
            | smartSize:width=600,height=250,poi=810,568,crop=medium | 0,0   | #1d2f55 | 600   | 250    |
            | smartSize:width=600,height=250,poi=810,568,crop=wide   | 0,0   | #1a2749 | 600   | 250    |
            | smartSize:width=250,height=600,poi=810,568,crop=close  | 0,0   | #5b8089 | 250   | 600    |
            | smartSize:width=250,height=600,poi=810,568,crop=medium | 0,0   | #aaab84 | 250   | 600    |
            | smartSize:width=250,height=600,poi=810,568,crop=wide   | 0,0   | #fafff3 | 250   | 600    |

    Scenario: Smart size based on POI stored in metadata
        Given I specify "smartSize:width=250,height=250" as transformation
        And I include an access token in the query string
        When I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the image dimension is "250x250"
        And the pixel at coordinate "0,0" has a color of "#355170"
        And the "X-Imbo-POIs-Used" response header is "1"

    Scenario: Smart size based on POI without center coordinates stored in metadata
        Given the request body is:
            """
            {"poi": [{"x": 800, "y": 558, "width": 20, "height": 20}]}
            """
        And I sign the request
        And I request the metadata of the previously added image using HTTP "PUT"
        When I specify "smartSize:width=250,height=250" as transformation
        And I include an access token in the query string
        And I request the previously added image as a "png"
        Then the response status line is "200 OK"
        And the image dimension is "250x250"
        And the pixel at coordinate "0,0" has a color of "#355170"
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
        And the pixel at coordinate "0,0" has a color of "<color>"
        And the "X-Imbo-POIs-Used" response header is "0"

        Examples:
            | color   | width | height |
            | #495569 | 250   | 250    |
            | #171c3a | 500   | 150    |
            | #feffef | 150   | 500    |
