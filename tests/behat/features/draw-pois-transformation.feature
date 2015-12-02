Feature: Imbo can read POIs from metadata and draw them on images
    In order to display stored POIs on an image
    As an HTTP Client
    I can use the drawPois transformation

    Background:
        Given "tests/behat/fixtures/faces.jpg" is used as the test image for the "drawpois" feature
        And I use "publickey" and "privatekey" for public and private keys
        And the request body contains:
          """
          {
            "poi": [{
                "x": 362,
                "y": 80,
                "cx": 467,
                "cy": 203,
                "width": 210,
                "height": 245
            }, {
                "x": 74,
                "y": 237,
                "cx": 98,
                "cy": 263,
                "width": 48,
                "height": 51
            }, {
                "cx": 653,
                "cy": 185
            }]
          }
          """
        And I sign the request
        When I request the metadata of the test image using HTTP "PUT"
        Then I should get a response with "200 OK"

    Scenario Outline: Draw POIs on image
        Given I specify "<transformation>" as transformation
        And I include an access token in the query
        When I request the test image as a "png"
        Then I should get a response with "200 OK"
        And the pixel at coordinate "<coord>" should have a color of "<color>"

        Examples:
            | transformation                      | coord    | color   |
            | drawPois                            | 362, 81  | #ff0000 |
            | drawPois                            | 659, 187 | #ffcec9 |
            | drawPois                            | 610, 187 | #ff0000 |
            | drawPois:borderSize=10              | 65, 250  | #ff0000 |
            | drawPois:borderSize=10              | 74, 250  | #ff0000 |
            | drawPois:borderSize=10              | 75, 250  | #6b4b36 |
            | drawPois:color=cc00cc               | 362, 81  | #cc00cc |
            | drawPois:pointSize=50,borderSize=5  | 608, 242 | #ff0000 |
