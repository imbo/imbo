Feature: Imbo can crop images using smart size and POIs
    In order to crop an image based on A POI
    As an HTTP Client
    I can use the smart size transformation

    Background:
        Given "tests/behat/fixtures/trolltunga.jpg" is used as the test image for the "smartsize" feature
        And I use "publickey" and "privatekey" for public and private keys
        And the request body contains:
          """
          {"poi": [{"x": 810, "y": 568}]}
          """
        And I sign the request
        When I request the metadata of the test image using HTTP "PUT"
        Then I should get a response with "200 OK"

    Scenario Outline: Smart size image
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And I specify "<transformation>" as transformation
        When I request the test image as a "png"
        Then I should get a response with "200 OK"
        And the width of the image is "<width>"
        And the height of the image is "<height>"
        And the pixel at coordinate "<coord>" should have a color of "<color>"

        Examples:
            | transformation                                         | coord | color   | width | height |
            | smartsize:width=250,height=250,poi=810,568,crop=close  | 0, 0  | #396473 | 250   | 250    |
            | smartsize:width=250,height=250,poi=810,568,crop=medium | 0, 0  | #355170 | 250   | 250    |
            | smartsize:width=250,height=250,poi=810,568,crop=wide   | 0, 0  | #52607c | 250   | 250    |

            | smartsize:width=600,height=250,poi=810,568,crop=close  | 0, 0  | #50748c | 600   | 250    |
            | smartsize:width=600,height=250,poi=810,568,crop=medium | 0, 0  | #1d2f55 | 600   | 250    |
            | smartsize:width=600,height=250,poi=810,568,crop=wide   | 0, 0  | #1a2749 | 600   | 250    |

            | smartsize:width=250,height=600,poi=810,568,crop=close  | 0, 0  | #5b8089 | 250   | 600    |
            | smartsize:width=250,height=600,poi=810,568,crop=medium | 0, 0  | #aaab84 | 250   | 600    |
            | smartsize:width=250,height=600,poi=810,568,crop=wide   | 0, 0  | #fafff3 | 250   | 600    |

    Scenario: Smart size based on POI stored in metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And I specify "smartsize:width=250,height=250" as transformation
        When I request the test image as a "png"
        Then I should get a response with "200 OK"
        And the width of the image is "250"
        And the height of the image is "250"
        And the pixel at coordinate "0, 0" should have a color of "#355170"
