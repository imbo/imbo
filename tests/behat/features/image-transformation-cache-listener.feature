Feature: Imbo enables caching of transformations
    In order to speed up image transformations
    As an image server
    I will cache and re-use transformed images

    Background:
        Given "tests/phpunit/Fixtures/image1.png" exists for user "user"
        And Imbo uses the "image-transformation-cache.php" configuration
        And I use "publicKey" and "privateKey" for public and private keys

    Scenario: Fetch uncached image, then fetch same image from cache
        When I request:
            | path                   | extension | method | access token |
            | previously added image | jpg       | GET    | yes          |
            | previously added image | jpg       | GET    | yes          |

        Then the last 2 responses match:
            | response | status line | header name                | header value |
            | 1        | 200 OK      | content-type               | image/jpeg   |
            | 1        |             | X-Imbo-TransformationCache | Miss         |
            | 2        | 200 OK      | content-type               | image/jpeg   |
            | 2        |             | X-Imbo-TransformationCache | Hit          |

    Scenario: Fetch the same image, but with a different extension
        When I request:
            | path                   | extension | method | access token |
            | previously added image | png       | GET    | yes          |
            | previously added image | png       | GET    | yes          |

        Then the last 2 responses match:
            | response | status line | header name                | header value | checksum                         |
            | 1        | 200 OK      | content-type               | image/png    | fc7d2d06993047a0b5056e8fac4462a2 |
            | 1        |             | X-Imbo-TransformationCache | Miss         |                                  |
            | 2        | 200 OK      | content-type               | image/png    | fc7d2d06993047a0b5056e8fac4462a2 |
            | 2        |             | X-Imbo-TransformationCache | Hit          |                                  |

    Scenario: Fetch image with extra transformations added
        When I request:
            | path                   | transformation                   | extension | method | access token |
            | previously added image | crop:width=50,height=60,x=1,y=10 | jpg       | GET    | yes          |
            | previously added image | crop:width=50,height=60,x=1,y=10 | jpg       | GET    | yes          |

        Then the last 2 responses match:
            | response | status line | header name                | header value | image width | image height |
            | 1        | 200 OK      | content-type               | image/jpeg   | 50          | 60           |
            | 1        |             | X-Imbo-TransformationCache | Miss         |             |              |
            | 2        | 200 OK      | content-type               | image/jpeg   | 50          | 60           |
            | 2        |             | X-Imbo-TransformationCache | Hit          |             |              |

    Scenario: Fetch an image to place it in the transformation cache, then delete it, and fetch it again
        When I request:
            | path                   | extension | method | sign request | access token |
            | previously added image | jpg       | GET    |              | yes          |
            | previously added image | jpg       | GET    |              | yes          |
            | previously added image |           | DELETE | yes          |              |
            | previously added image | jpg       | GET    |              | yes          |

        Then the last 4 responses match:
            | response | status line         | header name                | header value |
            | 1        | 200 OK              | content-type               | image/jpeg   |
            | 1        |                     | X-Imbo-TransformationCache | Miss         |
            | 2        | 200 OK              | content-type               | image/jpeg   |
            | 2        |                     | X-Imbo-TransformationCache | Hit          |
            | 3        | 200 OK              |                            |              |
            | 4        | 404 Image not found |                            |              |
