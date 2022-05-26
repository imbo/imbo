Feature: Imbo provides an images endpoint
    In order to query images
    As an HTTP Client
    I want to make requests against the images endpoint

    Background:
        Given "tests/Fixtures/image1.png" exists for user "user"
        And "tests/Fixtures/image.jpg" exists for user "user"
        And "tests/Fixtures/image.gif" exists for user "user"
        And "tests/Fixtures/1024x256.png" exists for user "user"

    Scenario: Fetch images with no filter
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user/images"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
                "search": {
                    "hits": 4,
                    "page": 1,
                    "limit": 20,
                    "count": 4
                },
                "images": "@arrayLength(4)"
            }
            """

    Scenario: Fetch images using limit
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user/images?limit=2"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
                "search": {
                    "hits": 4,
                    "page": 1,
                    "limit": 2,
                    "count": 2
                },
                "images": "@arrayLength(2)"
            }
            """

    Scenario: Fetch images with a filter on non-existant image identifier
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user/images?ids[]=foobar"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
                "search": {
                    "hits": 0,
                    "page": 1,
                    "limit": 20,
                    "count": 0
                },
                "images": "@arrayLength(0)"
            }
            """

    Scenario: Fetch images with a filter on existing image identifier
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        And the query string parameter "ids[]" is set to the image identifier of "tests/Fixtures/image1.png"
        When I request "/users/user/images"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
                "search": {
                    "hits": 1,
                    "page": 1,
                    "limit": 20,
                    "count": 1
                },
                "images": "@arrayLength(1)",
                "images[0]": {
                    "added": "@isDate()",
                    "updated": "@isDate()",
                    "checksum": "fc7d2d06993047a0b5056e8fac4462a2",
                    "originalChecksum": "fc7d2d06993047a0b5056e8fac4462a2",
                    "extension": "png",
                    "size": 95576,
                    "width": 599,
                    "height": 417,
                    "mime": "image/png",
                    "mimeType": "image/png",
                    "imageIdentifier": "@regExp(/^[a-zA-Z0-9-_]{12}$/)",
                    "user": "user"
                }
            }
            """

    Scenario Outline: Fetch images with a filter on checksums
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user/images.json?checksums[]=<filter>"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            <response>
            """

        Examples:
            | filter                           | response                                                                                                                                                                                                                                                                                                                                                    |
            | foobar                           | {"search":{"hits":0,"page":1,"limit":20,"count":0},"images":[]}                                                                                                                                                                                                                                                                                             |
            | fc7d2d06993047a0b5056e8fac4462a2 | {"search":{"hits":1,"page":1,"limit":20,"count":1},"images":[{"added":"@isDate()","updated":"@isDate()","checksum":"fc7d2d06993047a0b5056e8fac4462a2","originalChecksum":"fc7d2d06993047a0b5056e8fac4462a2","extension":"png","size":95576,"width":599,"height":417,"mime":"image/png","mimeType":"image/png","imageIdentifier":"@regExp(/^[a-zA-Z0-9-_]{12}$/)","user":"user"}]}  |
            | f3210f1bb34bfbfa432cc3560be40761 | {"search":{"hits":1,"page":1,"limit":20,"count":1},"images":[{"added":"@isDate()","updated":"@isDate()","checksum":"f3210f1bb34bfbfa432cc3560be40761","originalChecksum":"f3210f1bb34bfbfa432cc3560be40761","extension":"jpg","size":64828,"width":665,"height":463,"mime":"image/jpeg","mimeType":"image/jpeg","imageIdentifier":"@regExp(/^[a-zA-Z0-9-_]{12}$/)","user":"user"}]} |
            | b5426b4c008e378c201526d2baaec599 | {"search":{"hits":1,"page":1,"limit":20,"count":1},"images":[{"added":"@isDate()","updated":"@isDate()","checksum":"b5426b4c008e378c201526d2baaec599","originalChecksum":"b5426b4c008e378c201526d2baaec599","extension":"gif","size":66020,"width":665,"height":463,"mime":"image/gif","mimeType":"image/gif","imageIdentifier":"@regExp(/^[a-zA-Z0-9-_]{12}$/)","user":"user"}]}  |

    Scenario Outline: Fetch images only displaying certain fields
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user/images.json?<fields>"
        Then the response status line is "200 OK"
        And the response body matches:
            """
            <response>
            """

        Examples:
            | fields                         | response                                                                                                                                      |
            | fields[]=size                  | #^{"search":{.*?},"images":\[{"size":\d+},{"size":\d+},{"size":\d+},{"size":\d+}\]}$#                                                         |
            | fields[]=width&fields[]=height | #^{"search":{.*?},"images":\[{"width":\d+,"height":\d+},{"width":\d+,"height":\d+},{"width":\d+,"height":\d+},{"width":\d+,"height":\d+}\]}$# |

    Scenario Outline: Fetch images with metadata
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user/images.json?metadata=1&fields[]=<fields>"
        Then the response status line is "200 OK"
        And the response body matches:
            """
            <response>
            """

        Examples:
            | fields       | response                                                                                          |
            | size         | #^{"search":{.*?},"images":\[{"size":\d+},{"size":\d+},{"size":\d+},{"size":\d+}\]}$#             |
            | metadata     | #^{"search":{.*?},"images":\[{"metadata":{}},{"metadata":{}},{"metadata":{}},{"metadata":{}}\]}$# |

    Scenario Outline: Fetch images and use custom sorting
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user/images.json?<fields>&<sort>"
        Then the response status line is "200 OK"
        And the response body matches:
            """
            <response>
            """

        Examples:
            | fields                        | sort                          | response                                                                                                                                       |
            | fields[]=size                 | sort[]=size                   | #^{"search":{.*?},"images":\[{"size":12505},{"size":64828},{"size":66020},{"size":95576}\]}$#                                                  |
            | fields[]=size                 | sort[]=size:desc              | #^{"search":{.*?},"images":\[{"size":95576},{"size":66020},{"size":64828},{"size":12505}\]}$#                                                  |
            | fields[]=size&fields[]=width  | sort[]=width&sort[]=size:desc | #^{"search":{.*?},"images":\[{"size":95576,"width":599},{"size":66020,"width":665},{"size":64828,"width":665},{"size":12505,"width":1024}\]}$# |
            | fields[]=size&fields[]=width  | sort[]=width&sort[]=size      | #^{"search":{.*?},"images":\[{"size":95576,"width":599},{"size":64828,"width":665},{"size":66020,"width":665},{"size":12505,"width":1024}\]}$# |

    Scenario: The hits number has the number of hits in the query
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        And the query string parameter "page" is set to 1
        And the query string parameter "limit" is set to 1
        And the query string parameter "ids[]" is set to the image identifier of "tests/Fixtures/image1.png"
        And the query string parameter "ids[]" is set to the image identifier of "tests/Fixtures/image.jpg"
        When I request "/users/user/images.json"
        Then the response status line is "200 OK"
        And the response body contains JSON:
            """
            {
                "search": {
                    "hits": 2,
                    "page": 1,
                    "limit": 1,
                    "count": 1
                },
                "images": "@arrayLength(1)"
            }
            """

    Scenario: Fetch images with a filter on original checksums
        Given I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request "/users/user/images.json?originalChecksums[]=b60df41830245ee8f278e3ddfe5238a3"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
                "search": {
                    "hits": 1,
                    "page": 1,
                    "limit": 20,
                    "count": 1
                },
                "images": "@arrayLength(1)",
                "images[0]":
                {
                    "added": "@isDate()",
                    "updated": "@isDate()",
                    "checksum": "b60df41830245ee8f278e3ddfe5238a3",
                    "originalChecksum": "b60df41830245ee8f278e3ddfe5238a3",
                    "extension": "png",
                    "size": 12505,
                    "width": 1024,
                    "height": 256,
                    "mime": "image/png",
                    "mimeType": "image/png",
                    "imageIdentifier": "@regExp(/^[a-zA-Z0-9-_]{12}$/)",
                    "user": "user"
                }
            }
            """
