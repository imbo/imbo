Feature: Imbo provides an images endpoint
    In order to query images
    As an HTTP Client
    I want to make requests against the images endpoint

    Background:
        Given "tests/phpunit/Fixtures/image1.png" exists for user "user" in Imbo
        And "tests/phpunit/Fixtures/image.jpg" exists for user "user" in Imbo
        And "tests/phpunit/Fixtures/image.gif" exists for user "user" in Imbo
        And "tests/phpunit/Fixtures/1024x256.png" exists for user "user" in Imbo

    Scenario: Fetch images with no filter
        Given I include an access token in the query using "publickey" and "privatekey"
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
        Given I include an access token in the query using "publickey" and "privatekey"
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
        Given I include an access token in the query using "publickey" and "privatekey"
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
        Given I include an access token in the query using "publickey" and "privatekey"
        And the query string parameter "ids[]" is set to the image identifier of "tests/phpunit/Fixtures/image1.png"
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
                "added": "@regExp(/^[a-zA-Z0-9 ,:]+$/)",
                "updated": "@regExp(/^[a-zA-Z0-9 ,:]+$/)",
                "checksum": "fc7d2d06993047a0b5056e8fac4462a2",
                "originalChecksum": "fc7d2d06993047a0b5056e8fac4462a2",
                "extension": "png",
                "size": 95576,
                "width": 599,
                "height": 417,
                "mime": "image/png",
                "imageIdentifier": "@regExp(/^[a-zA-Z0-9-_]{12}$/)",
                "user": "user"
            }
        }
        """

    Scenario Outline: Fetch images with a filter on checksums
        Given I include an access token in the query using "publickey" and "privatekey"
        When I request "/users/user/images.json?checksums[]=<filter>"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | filter                           | response |
            | foobar                           | #^{"search":{.*?},"images":\[\]}$# |
            | fc7d2d06993047a0b5056e8fac4462a2 | #^{"search":{.*?},"images":\[{"added":"[^"]+","updated":"[^"]+","checksum":"fc7d2d06993047a0b5056e8fac4462a2","originalChecksum":"fc7d2d06993047a0b5056e8fac4462a2","extension":"png","size":95576,"width":599,"height":417,"mime":"image\\/png","imageIdentifier":".*?","user":"user"}\]}$# |
            | f3210f1bb34bfbfa432cc3560be40761 | #^{"search":{.*?},"images":\[{"added":"[^"]+","updated":"[^"]+","checksum":"f3210f1bb34bfbfa432cc3560be40761","originalChecksum":"f3210f1bb34bfbfa432cc3560be40761","extension":"jpg","size":64828,"width":665,"height":463,"mime":"image\\/jpeg","imageIdentifier":".*?","user":"user"}\]}$# |
            | b5426b4c008e378c201526d2baaec599 | #^{"search":{.*?},"images":\[{"added":"[^"]+","updated":"[^"]+","checksum":"b5426b4c008e378c201526d2baaec599","originalChecksum":"b5426b4c008e378c201526d2baaec599","extension":"gif","size":66020,"width":665,"height":463,"mime":"image\\/gif","imageIdentifier":".*?","user":"user"}\]}$# |

    Scenario Outline: Fetch images only displaying certain fields
        Given I include an access token in the query using "publickey" and "privatekey"
        When I request "/users/user/images.<extension>?<fields>"
        Then the response status line is "200 OK"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | extension | fields                         | response |
            | json      | fields[]=size                  | #^{"search":{.*?},"images":\[{"size":\d+},{"size":\d+},{"size":\d+},{"size":\d+}\]}$# |
            | json      | fields[]=width&fields[]=height | #^{"search":{.*?},"images":\[{"width":\d+,"height":\d+},{"width":\d+,"height":\d+},{"width":\d+,"height":\d+},{"width":\d+,"height":\d+}\]}$# |

    Scenario Outline: Fetch images with metadata
        Given I include an access token in the query using "publickey" and "privatekey"
        When I request "/users/user/images.<extension>?metadata=1&fields[]=<fields>"
        Then the response status line is "200 OK"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | extension | fields       | response |
            | json      | size         | #^{"search":{.*?},"images":\[{"size":\d+},{"size":\d+},{"size":\d+},{"size":\d+}\]}$# |
            | json      | metadata     | #^{"search":{.*?},"images":\[{"metadata":{}},{"metadata":{}},{"metadata":{}},{"metadata":{}}\]}$# |

    Scenario Outline: Fetch images and use custom sorting
        Given I include an access token in the query using "publickey" and "privatekey"
        When I request "/users/user/images.json?<fields>&<sort>"
        Then the response status line is "200 OK"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | fields                        | sort                          | response |
            | fields[]=size                 | sort[]=size                   | #^{"search":{.*?},"images":\[{"size":12505},{"size":64828},{"size":66020},{"size":95576}\]}$# |
            | fields[]=size                 | sort[]=size:desc              | #^{"search":{.*?},"images":\[{"size":95576},{"size":66020},{"size":64828},{"size":12505}\]}$# |
            | fields[]=size&fields[]=width  | sort[]=width&sort[]=size:desc | #^{"search":{.*?},"images":\[{"size":95576,"width":599},{"size":66020,"width":665},{"size":64828,"width":665},{"size":12505,"width":1024}\]}$# |
            | fields[]=size&fields[]=width  | sort[]=width&sort[]=size      | #^{"search":{.*?},"images":\[{"size":95576,"width":599},{"size":64828,"width":665},{"size":66020,"width":665},{"size":12505,"width":1024}\]}$# |

    Scenario: The hits number has the number of hits in the query
        Given I include an access token in the query using "publickey" and "privatekey"
        And I append a query string parameter, "page" with the value "1"
        And I append a query string parameter, "limit" with the value "1"
        And I append a query string parameter, "ids[]" with the image identifier of "tests/phpunit/Fixtures/image1.png"
        And I append a query string parameter, "ids[]" with the image identifier of "tests/phpunit/Fixtures/image.jpg"
        When I request "/users/user/images.json" with the given query string
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
                "images": "@arrayLength(2)"
            }
            """

    Scenario: Fetch images with a filter on original checksums
        Given I include an access token in the query using "publickey" and "privatekey"
        When I request "/users/user/images.json?originalChecksums[]=b60df41830245ee8f278e3ddfe5238a3"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
                "search": {
                    "hits": 111,
                    "page": 122,
                    "limit": 333,
                    "count": 444
                },
                "images":
                [
                    {
                        "added": "added",
                        "updated": "updated",
                        "checksum": "b60df41830245ee8f278e3ddfe5238a3",
                        "originalChecksum": "b60df41830245ee8f278e3ddfe5238a3",
                        "extension": "png",
                        "size": 12505,
                        "width": 1024,
                        "height": 256,
                        "mime": "image/png",
                        "imageIdentifier": "imageIdentifier",
                        "user": "user"
                    }
                ]
            }
            """
