Feature: Imbo enables dynamic transformations of images
    In order to transform images
    As an HTTP Client
    I can specify image transformations as query parameters

    Scenario: Add an image
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        And I attach "tests/Imbo/Fixtures/image1.png" to the request body
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2" using HTTP "PUT"
        And the "Content-Type" response header is "application/json"
        And the response body is:
          """
          {"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2"}
          """

    Scenario Outline: Transform the image
        Given I use "publickey" and "privatekey" for public and private keys
        And the "Accept" request header is "image/png"
        And I specify "<transformation>" as transformation
        And I include an access token in the query
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/png"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"
        And the width of the image is "<width>"
        And the height of the image is "<height>"

        Examples:
            | transformation                   | width | height |
            | border                           | 601   | 419    |
            | border:width=4,height=5          | 607   | 427    |
            | canvas                           | 599   | 417    |
            | canvas:width=700,height=600      | 700   | 600    |
            | crop:width=50,height=60,x=1,y=10 | 50    | 60     |
            | desaturate                       | 599   | 417    |
            | flipHorizontally                 | 599   | 417    |
            | flipVertically                   | 599   | 417    |
            | maxSize:width=200                | 200   | 139    |
            | maxSize:height=200               | 287   | 200    |
            | maxSize:width=100,height=100     | 100   | 70     |
            | resize:width=100                 | 100   | 69     |
            | resize:height=200                | 287   | 200    |
            | resize:width=100,height=100      | 100   | 100    |
            | rotate:angle=90                  | 417   | 599    |
            | sepia                            | 599   | 417    |
            | thumbnail                        | 50    | 50     |
            | thumbnail:width=40,height=30     | 40    | 30     |
            | transpose                        | 417   | 599    |
            | transverse                       | 417   | 599    |


    Scenario Outline: Gracefully handle transformation errors
        Given I use "publickey" and "privatekey" for public and private keys
        And the "Accept" request header is "application/xml,image/png"
        And I specify "<transformation>" as transformation
        And I include an access token in the query
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2"
        Then I should get a response with "<reason-phrase>"
        And the "Content-Type" response header is "application/xml"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"

        Examples:
            | transformation            | reason-phrase                                                               |
            | compress                  | 400 Missing required parameter: quality                                     |
            | crop:width=100            | 400 Missing required parameter: height                                      |
            | resize                    | 400 Missing both width and height. You need to specify at least one of them |
            | rotate                    | 400 Missing required parameter: angle                                       |
