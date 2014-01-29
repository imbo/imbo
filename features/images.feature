Feature: Imbo provides an images endpoint
    In order to query images
    As an HTTP Client
    I want to make requests against the images endpoint

    Background:
        Given "tests/Fixtures/image1.png" exists in Imbo
        And "tests/Fixtures/image.jpg" exists in Imbo
        And "tests/Fixtures/image.gif" exists in Imbo

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
            | extension | content-type     | response |
            | json      | application/json | #^{"search":{"hits":3,"page":1,"limit":20,"count":3},"images":\[{.*?},{.*?},{.*?}\]}$# |
            | xml       | application/xml  | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<search>\s*<hits>3</hits>\s*<page>1</page>\s*<limit>20</limit>\s*<count>3</count>\s*</search>\s*<images>\s*(<image>.*?</image>\s*){3}\s*</images>\s*</imbo>$#ms |

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
            | extension | content-type     | response |
            | json      | application/json | #^{"search":{"hits":3,"page":1,"limit":2,"count":2},"images":\[{.*?},{.*?}\]}$# |
            | xml       | application/xml  | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<search>\s*<hits>3</hits>\s*<page>1</page>\s*<limit>2</limit>\s*<count>2</count>\s*</search>\s*<images>\s*(<image>.*?</image>\s*){2}\s*</images>\s*</imbo>$#ms |

    Scenario Outline: Fetch images with a filter on image identifiers
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images.json?ids[]=<filter>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | filter                           | response |
            | foobar                           | #^{"search":{.*?},"images":\[\]}$# |
            | fc7d2d06993047a0b5056e8fac4462a2 | #^{"search":{.*?},"images":\[{"added":"[^"]+","updated":"[^"]+","checksum":"fc7d2d06993047a0b5056e8fac4462a2","extension":"png","size":95576,"width":599,"height":417,"mime":"image\\/png","imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2","publicKey":"publickey"}\]}$# |
            | f3210f1bb34bfbfa432cc3560be40761 | #^{"search":{.*?},"images":\[{"added":"[^"]+","updated":"[^"]+","checksum":"f3210f1bb34bfbfa432cc3560be40761","extension":"jpg","size":64828,"width":665,"height":463,"mime":"image\\/jpeg","imageIdentifier":"f3210f1bb34bfbfa432cc3560be40761","publicKey":"publickey"}\]}$# |
            | b5426b4c008e378c201526d2baaec599 | #^{"search":{.*?},"images":\[{"added":"[^"]+","updated":"[^"]+","checksum":"b5426b4c008e378c201526d2baaec599","extension":"gif","size":66020,"width":665,"height":463,"mime":"image\\/gif","imageIdentifier":"b5426b4c008e378c201526d2baaec599","publicKey":"publickey"}\]}$# |

    Scenario Outline: Fetch images with a filter on checksums
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images.json?checksums[]=<filter>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | filter                           | response |
            | foobar                           | #^{"search":{.*?},"images":\[\]}$# |
            | fc7d2d06993047a0b5056e8fac4462a2 | #^{"search":{.*?},"images":\[{"added":"[^"]+","updated":"[^"]+","checksum":"fc7d2d06993047a0b5056e8fac4462a2","extension":"png","size":95576,"width":599,"height":417,"mime":"image\\/png","imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2","publicKey":"publickey"}\]}$# |
            | f3210f1bb34bfbfa432cc3560be40761 | #^{"search":{.*?},"images":\[{"added":"[^"]+","updated":"[^"]+","checksum":"f3210f1bb34bfbfa432cc3560be40761","extension":"jpg","size":64828,"width":665,"height":463,"mime":"image\\/jpeg","imageIdentifier":"f3210f1bb34bfbfa432cc3560be40761","publicKey":"publickey"}\]}$# |
            | b5426b4c008e378c201526d2baaec599 | #^{"search":{.*?},"images":\[{"added":"[^"]+","updated":"[^"]+","checksum":"b5426b4c008e378c201526d2baaec599","extension":"gif","size":66020,"width":665,"height":463,"mime":"image\\/gif","imageIdentifier":"b5426b4c008e378c201526d2baaec599","publicKey":"publickey"}\]}$# |

    Scenario Outline: Fetch images only displaying certain fields
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images.<extension>?<fields>"
        Then I should get a response with "200 OK"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | extension | fields                         | response |
            | json      | fields[]=size                  | #^{"search":{.*?},"images":\[{"size":\d+},{"size":\d+},{"size":\d+}\]}$# |
            | xml       | fields[]=size                  | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<search>.*?</search>\s*<images><image><size>\d+</size></image><image><size>\d+</size></image><image><size>\d+</size></image></images>\s*</imbo>$#ms |
            | json      | fields[]=width&fields[]=height | #^{"search":{.*?},"images":\[{"width":\d+,"height":\d+},{"width":\d+,"height":\d+},{"width":\d+,"height":\d+}\]}$# |
            | xml       | fields[]=width&fields[]=height | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<search>.*?</search>\s*<images><image><width>\d+</width><height>\d+</height></image><image><width>\d+</width><height>\d+</height></image><image><width>\d+</width><height>\d+</height></image></images>\s*</imbo>$#ms |

    Scenario Outline: Fetch images with metadata
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images.<extension>?metadata=1&fields[]=<fields>"
        Then I should get a response with "200 OK"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | extension | fields       | response |
            | json      | size         | #^{"search":{.*?},"images":\[{"size":\d+},{"size":\d+},{"size":\d+}\]}$# |
            | xml       | size         | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<search>.*?</search>\s*<images><image><size>\d+</size></image><image><size>\d+</size></image><image><size>\d+</size></image></images>\s*</imbo>$#ms |
            | json      | metadata     | #^{"search":{.*?},"images":\[{"metadata":{}},{"metadata":{}},{"metadata":{}}\]}$# |
            | xml       | metadata     | #^<\?xml version="1.0" encoding="UTF-8"\?>\s*<imbo>\s*<search>.*?</search>\s*<images><image><metadata></metadata></image><image><metadata></metadata></image><image><metadata></metadata></image></images>\s*</imbo>$#ms |

    Scenario Outline: Fetch images and use custom sorting
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images.json?<fields>&<sort>"
        Then I should get a response with "200 OK"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | fields                        | sort                          | response |
            | fields[]=size                 | sort[]=size                   | #^{"search":{.*?},"images":\[{"size":64828},{"size":66020},{"size":95576}\]}$# |
            | fields[]=size                 | sort[]=size:desc              | #^{"search":{.*?},"images":\[{"size":95576},{"size":66020},{"size":64828}\]}$# |
            | fields[]=size&fields[]=width  | sort[]=width&sort[]=size:desc | #^{"search":{.*?},"images":\[{"size":95576,"width":599},{"size":66020,"width":665},{"size":64828,"width":665}\]}$# |
            | fields[]=size&fields[]=width  | sort[]=width&sort[]=size      | #^{"search":{.*?},"images":\[{"size":95576,"width":599},{"size":64828,"width":665},{"size":66020,"width":665}\]}$# |

    Scenario: The hits number has the number of hits in the query
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images.json?limit=1&page=1&ids[]=fc7d2d06993047a0b5056e8fac4462a2&ids[]=b5426b4c008e378c201526d2baaec599"
        Then I should get a response with "200 OK"
        And the response body matches:
        """
        #^{"search":{"hits":2,"page":1,"limit":1,"count":1},"images":\[{.*}\]}$#
        """
