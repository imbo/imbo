Feature: Imbo enables dynamic transformations of images
    In order to transform images
    As an HTTP Client
    I can specify image transformations as query parameters

    Background:
        Given "tests/phpunit/Fixtures/image1.png" exists in Imbo
        And "tests/phpunit/Fixtures/image.png" exists in Imbo

    Scenario Outline: Transform the image
        Given I use "publickey" and "privatekey" for public and private keys
        And I specify "<transformation>" as transformation
        And I include an access token in the query
        And Imbo uses the "image-transformation-presets.php" configuration
        When I request the image resource for "tests/phpunit/Fixtures/image1.png" as a "png"
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
            | transformation                                                                                    | width | height |
            | blur:radius=2,sigma=4                                                                             | 599   | 417    |
            | blur:angle=5,type=radial                                                                          | 599   | 417    |
            | blur:radius=20,sigma=10,angle=70,type=motion                                                      | 599   | 417    |
            | blur:radius=2,sigma=4,type=adaptive                                                               | 599   | 417    |
            | border                                                                                            | 601   | 419    |
            | border:width=4,height=5                                                                           | 607   | 427    |
            | border:mode=inline,width=4,height=5                                                               | 599   | 417    |
            | canvas                                                                                            | 599   | 417    |
            | canvas:width=700,height=600                                                                       | 700   | 600    |
            | contrast                                                                                          | 599   | 417    |
            | contrast:sharpen:-1                                                                               | 599   | 417    |
            | contrast:sharpen:1                                                                                | 599   | 417    |
            | crop:width=50,height=60,x=1,y=10                                                                  | 50    | 60     |
            | crop:width=599,height=417,x=0,y=0                                                                 | 599   | 417    |
            | crop:mode=center,width=100,height=100                                                             | 100   | 100    |
            | crop:mode=center,width=599,height=417                                                             | 599   | 417    |
            | crop:mode=center-x,y=10,width=123,height=20                                                       | 123   | 20     |
            | crop:mode=center-y,x=10,width=234,height=30                                                       | 234   | 30     |
            | desaturate                                                                                        | 599   | 417    |
            | flipHorizontally                                                                                  | 599   | 417    |
            | flipVertically                                                                                    | 599   | 417    |
            | histogram                                                                                         | 256   | 158    |
            | histogram:scale=2                                                                                 | 512   | 316    |
            | histogram:scale=2,ratio=2                                                                         | 512   | 256    |
            | level                                                                                             | 599   | 417    |
            | level:channel=r,amount=40                                                                         | 599   | 417    |
            | level:channel=cm,amount=-30                                                                       | 599   | 417    |
            | maxSize:width=200                                                                                 | 200   | 139    |
            | maxSize:height=200                                                                                | 287   | 200    |
            | maxSize:width=100,height=100                                                                      | 100   | 70     |
            | modulate                                                                                          | 599   | 417    |
            | modulate:b=1,s=2                                                                                  | 599   | 417    |
            | modulate:b=1,s=2,h=3                                                                              | 599   | 417    |
            | progressive                                                                                       | 599   | 417    |
            | resize:width=100                                                                                  | 100   | 70     |
            | resize:height=200                                                                                 | 288   | 200    |
            | resize:width=100,height=100                                                                       | 100   | 100    |
            | rotate:angle=90                                                                                   | 417   | 599    |
            | sepia                                                                                             | 599   | 417    |
            | sharpen                                                                                           | 599   | 417    |
            | sharpen:radius=2,sigma=1                                                                          | 599   | 417    |
            | sharpen:radius=0,sigma=2                                                                          | 599   | 417    |
            | sharpen:preset=light                                                                              | 599   | 417    |
            | sharpen:preset=moderate                                                                           | 599   | 417    |
            | sharpen:preset=strong                                                                             | 599   | 417    |
            | sharpen:preset=extreme                                                                            | 599   | 417    |
            | smartSize:width=250,height=400,poi=0\,0                                                           | 250   | 400    |
            | smartSize:width=700,height=300,poi=0\,0                                                           | 700   | 300    |
            | smartSize:width=300,height=300,poi=0\,0                                                           | 300   | 300    |
            | strip                                                                                             | 599   | 417    |
            | thumbnail                                                                                         | 50    | 50     |
            | thumbnail:width=40,height=30                                                                      | 40    | 30     |
            | thumbnail:width=40,height=40,fit=inset                                                            | 40    | 27     |
            | thumbnail:width=10,height=70,fit=inset                                                            | 10    | 6      |
            | transpose                                                                                         | 417   | 599    |
            | transverse                                                                                        | 417   | 599    |
            | graythumb:width=40,height=40                                                                      | 40    | 40     |
            | vignette                                                                                          | 599   | 417    |
            | vignette:inner=bf1942,outer=ccc                                                                   | 599   | 417    |
            | vignette:inner=f00baa,outer=f0f0f0,scale=2.4                                                      | 599   | 417    |

    Scenario Outline: Transform the image using HTTP HEAD
        Given I use "publickey" and "privatekey" for public and private keys
        And I specify "<transformation>" as transformation
        And I include an access token in the query
        And Imbo uses the "image-transformation-presets.php" configuration
        When I request the image resource for "tests/phpunit/Fixtures/image1.png" as a "png" using HTTP "HEAD"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/png"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"

        Examples:
            | transformation                                                                                    |
            | blur:radius=2,sigma=4                                                                             |
            | blur:angle=5,type=radial                                                                          |
            | blur:radius=20,sigma=10,angle=70,type=motion                                                      |
            | blur:radius=2,sigma=4,type=adaptive                                                               |
            | border                                                                                            |
            | border:width=4,height=5                                                                           |
            | border:mode=inline,width=4,height=5                                                               |
            | canvas                                                                                            |
            | canvas:width=700,height=600                                                                       |
            | contrast:sharpen=1                                                                                |
            | crop:width=50,height=60,x=1,y=10                                                                  |
            | crop:width=599,height=417,x=0,y=0                                                                 |
            | desaturate                                                                                        |
            | drawPois                                                                                          |
            | flipHorizontally                                                                                  |
            | flipVertically                                                                                    |
            | histogram                                                                                         |
            | histogram:scale=2                                                                                 |
            | histogram:scale=2,ratio=2                                                                         |
            | level                                                                                             |
            | level:channel=r                                                                                   |
            | level:channel=cm,amount=-30                                                                       |
            | maxSize:width=200                                                                                 |
            | maxSize:height=200                                                                                |
            | maxSize:width=100,height=100                                                                      |
            | modulate                                                                                          |
            | modulate:b=1,s=2                                                                                  |
            | modulate:b=1,s=2,h=3                                                                              |
            | progressive                                                                                       |
            | resize:width=100                                                                                  |
            | resize:height=200                                                                                 |
            | resize:width=100,height=100                                                                       |
            | rotate:angle=90                                                                                   |
            | sepia                                                                                             |
            | sharpen                                                                                           |
            | sharpen:radius=2,sigma=1                                                                          |
            | sharpen:radius=0,sigma=2                                                                          |
            | sharpen:preset=light                                                                              |
            | sharpen:preset=moderate                                                                           |
            | sharpen:preset=strong                                                                             |
            | sharpen:preset=extreme                                                                            |
            | smartSize:width=300,height=300,poi=0\,0                                                           |
            | strip                                                                                             |
            | thumbnail                                                                                         |
            | thumbnail:width=40,height=30                                                                      |
            | thumbnail:width=40,height=40,fit=inset                                                            |
            | thumbnail:width=10,height=70,fit=inset                                                            |
            | transpose                                                                                         |
            | transverse                                                                                        |
            | graythumb:width=40,height=40                                                                      |
            | vignette                                                                                          |
            | vignette:inner=bf1942,outer=ccc                                                                   |
            | vignette:inner=f00baa,outer=f0f0f0,scale=2.4                                                      |

    Scenario Outline: Gracefully handle transformation errors
        Given I use "publickey" and "privatekey" for public and private keys
        And I specify "<transformation>" as transformation
        And I include an access token in the query
        When I request the image resource for "tests/phpunit/Fixtures/image1.png" as a "png"
        Then I should get a response with "<reason-phrase>"
        And the "Content-Type" response header is "application/json"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"

        Examples:
            | transformation                    | reason-phrase                                                               |
            | blur                              | 400 Missing required parameter: radius                                      |
            | blur:radius=2                     | 400 Missing required parameter: sigma                                       |
            | blur:type=foobar                  | 400 Unknown blur type: foobar                                               |
            | blur:type=radial                  | 400 Missing required parameter: angle                                       |
            | blur:radius=2,type=motion         | 400 Missing required parameter: sigma                                       |
            | blur:sigma=1,type=motion          | 400 Missing required parameter: radius                                      |
            | blur:sigma=1,radius=2,type=motion | 400 Missing required parameter: angle                                       |
            | blur:type=radial                  | 400 Missing required parameter: angle                                       |
            | compress                          | 400 Missing required parameter: level                                       |
            | compress:level=200                | 400 level must be between 0 and 100                                         |
            | compress:level=-10                | 400 level must be between 0 and 100                                         |
            | crop:width=100                    | 400 Missing required parameter: height                                      |
            | resize                            | 400 Missing both width and height. You need to specify at least one of them |
            | rotate                            | 400 Missing required parameter: angle                                       |
            | smartSize                         | 400 Both width and height needs to be specified                             |
            | smartSize:height=300              | 400 Both width and height needs to be specified                             |
            | smartSize:width=300               | 400 Both width and height needs to be specified                             |

    Scenario: Support multiple transformations
        Given I use "publickey" and "privatekey" for public and private keys
        And I specify the following transformations:
          """
          resize:width=100,height=100
          resize:width=123,height=456
          desaturate
          drawPois
          flipHorizontally
          flipVertically
          thumbnail:width=40,height=30
          border:mode=inline
          transpose
          transverse
          sepia
          strip
          """
        And I include an access token in the query
        When I request the image resource for "tests/phpunit/Fixtures/image1.png" as a "png"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/png"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"
        And the width of the image is "40"
        And the height of the image is "30"

    Scenario Outline: Fetch different formats of the image based on the Accept header
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "<accept>"
        When I request the image resource for "tests/phpunit/Fixtures/image1.png"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"

        Examples:
            | accept     | content-type |
            | image/gif  | image/gif    |
            | image/jpeg | image/jpeg   |
            | image/png  | image/png    |

    Scenario Outline: Fetch different formats of the image based on the image extension
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request the image resource for "tests/phpunit/Fixtures/image1.png" as a "<extension>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"

        Examples:
            | extension | content-type |
            | gif       | image/gif    |
            | jpg       | image/jpeg   |
            | png       | image/png    |
