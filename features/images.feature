Feature: Imbo provides an images endpoint
    In order to query images
    As an HTTP Client
    I want to make requests against the images endpoint

    Background:
        Given "tests/Imbo/Fixtures/image1.png" exists in Imbo with identifier "fc7d2d06993047a0b5056e8fac4462a2"
        And "tests/Imbo/Fixtures/image.jpg" exists in Imbo with identifier "f3210f1bb34bfbfa432cc3560be40761"
        And "tests/Imbo/Fixtures/image.gif" exists in Imbo with identifier "b5426b4c008e378c201526d2baaec599"

    Scenario Outline: Fetch images with no filter
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images.<extension>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the response body matches:
        """
        <response>
        """
        Examples:
            | extension | content-type     | response                                                                                                              |
            | json      | application/json | #^\[{.*?},{.*?},{.*?}\]$#                                                                                             |
            | xml       | application/xml  | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<images>\s*(<image>.*?</image>\s*){3}\s*</images>\s*</imbo>$#ms |

    Scenario Outline: Fetch images using limit
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images.<extension>?limit=2"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the response body matches:
        """
        <response>
        """
        Examples:
            | extension | content-type     | response                                                                                                              |
            | json      | application/json | #^\[{.*?},{.*?}\]$#                                                                                                   |
            | xml       | application/xml  | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<images>\s*(<image>.*?</image>\s*){2}\s*</images>\s*</imbo>$#ms |

    Scenario Outline: Fetch images using image identifier filter
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images.json?imageIdentifiers=<filter>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | filter                           | response |
            | foobar                           | #^\[\]$# |
            | fc7d2d06993047a0b5056e8fac4462a2 | #^\[{"added":"[^"]+","updated":"[^"]+","checksum":"fc7d2d06993047a0b5056e8fac4462a2","extension":"png","size":95576,"width":599,"height":417,"mime":"image\\/png","imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2","publicKey":"publickey"}\]$# |
            | f3210f1bb34bfbfa432cc3560be40761 | #^\[{"added":"[^"]+","updated":"[^"]+","checksum":"f3210f1bb34bfbfa432cc3560be40761","extension":"jpg","size":64828,"width":665,"height":463,"mime":"image\\/jpeg","imageIdentifier":"f3210f1bb34bfbfa432cc3560be40761","publicKey":"publickey"}\]$# |
            | b5426b4c008e378c201526d2baaec599 | #^\[{"added":"[^"]+","updated":"[^"]+","checksum":"b5426b4c008e378c201526d2baaec599","extension":"gif","size":66020,"width":665,"height":463,"mime":"image\\/gif","imageIdentifier":"b5426b4c008e378c201526d2baaec599","publicKey":"publickey"}\]$# |

    Scenario Outline: Fetch images only displaying certain fields
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images.<extension>?fields=<fields>"
        Then I should get a response with "200 OK"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | extension | fields       | response |
            | json      | size         | #^\[{"size":\d+},{"size":\d+},{"size":\d+}\]$# |
            | xml       | size         | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<images><image><size>\d+</size></image><image><size>\d+</size></image><image><size>\d+</size></image></images>\s*</imbo>$# |
            | json      | width,height | #^\[{"width":\d+,"height":\d+},{"width":\d+,"height":\d+},{"width":\d+,"height":\d+}\]$# |
            | xml       | width,height | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<images><image><width>\d+</width><height>\d+</height></image><image><width>\d+</width><height>\d+</height></image><image><width>\d+</width><height>\d+</height></image></images>\s*</imbo>$# |

    Scenario Outline: Fetch images with metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images.<extension>?metadata=1&fields=<fields>"
        Then I should get a response with "200 OK"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | extension | fields       | response |
            | json      | size         | #^\[{"size":\d+},{"size":\d+},{"size":\d+}\]$# |
            | xml       | size         | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<images><image><size>\d+</size></image><image><size>\d+</size></image><image><size>\d+</size></image></images>\s*</imbo>$# |
            | json      | metadata     | #^\[{"metadata":{}},{"metadata":{}},{"metadata":{}}\]$# |
            | xml       | metadata     | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<images><image><metadata></metadata></image><image><metadata></metadata></image><image><metadata></metadata></image></images>\s*</imbo>$# |

    Scenario Outline: Fetch images and use custom sorting
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images.json?fields=<fields>&sort=<sort>"
        Then I should get a response with "200 OK"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | fields       | sort            | response |
            | size         | size            | #^\[{"size":64828},{"size":66020},{"size":95576}\]$# |
            | size         | size:desc       | #^\[{"size":95576},{"size":66020},{"size":64828}\]$# |
            | size,width   | width,size:desc | #^\[{"size":95576,"width":599},{"size":66020,"width":665},{"size":64828,"width":665}\]$# |
            | size,width   | width,size      | #^\[{"size":95576,"width":599},{"size":64828,"width":665},{"size":66020,"width":665}\]$# |
