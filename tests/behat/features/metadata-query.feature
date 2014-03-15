Feature: Imbo supports metadata queries
    In order to find images
    As an end user
    I want to perform metadata queries against the images endpoint

    Background:
        # fc7d2d06993047a0b5056e8fac4462a2
        Given "tests/phpunit/Fixtures/image1.png" exists in Imbo with the following metadata:
            """
            {"brewery":"Nøgne Ø","beer":"India Pale Ale","style":"IPA","abv":7.5,"review":"url"}
            """

        # f3210f1bb34bfbfa432cc3560be40761
        Given "tests/phpunit/Fixtures/image.jpg" exists in Imbo with the following metadata:
            """
            {"brewery":"Nøgne Ø","beer":"Dark Horizon","style":"Imperial Stout","abv":16,"review":"url"}
            """

        # b914b28f4d5faa516e2049b9a6a2577c
        Given "tests/phpunit/Fixtures/image2.png" exists in Imbo with the following metadata:
            """
            {"brewery":"HaandBryggeriet","beer":"Dark Force","style":"Imperial Stout","abv":9,"review":"url"}
            """

        # 1d5b88aec8a3e1c4c57071307b2dae3a
        Given "tests/phpunit/Fixtures/image3.png" exists in Imbo with the following metadata:
            """
            {"brewery":"Ægir","beer":"Witbier","style":"Witbier","abv":4.7}
            """

        # a501051db16e3cbf88ea50bfb0138a47
        Given "tests/phpunit/Fixtures/image4.png" exists in Imbo with the following metadata:
            """
            {"brewery":"Lervig","beer":"Johnny Low","style":"Session IPA","abv":2.5}
            """

        # 929db9c5fc3099f7576f5655207eba47
        Given "tests/phpunit/Fixtures/image.png" exists in Imbo with the following metadata:
            """
            {"brewery":"Kinn","beer":"Vestkyst","style":"IPA","abv":7}
            """

    Scenario Outline: Find images using metadata queries
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And specify "<query>" as metadata query
        When I request "/users/publickey/images.json?fields[]=imageIdentifier&sort[]=imageIdentifier"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | query                                              | response |
            | {"beer":"Dark Force"} | #^{"search":{.*},"images":\[{"imageIdentifier":"b914b28f4d5faa516e2049b9a6a2577c"}\]}$# |
            | {"brewery":"Nøgne Ø","beer":"Dark Horizon"} | #^{"search":{.*},"images":\[{"imageIdentifier":"f3210f1bb34bfbfa432cc3560be40761"}\]}$# |
            | {"$or":[{"brewery":"Lervig"},{"brewery":"Ægir"}]} | #^{"search":{.*},"images":\[{"imageIdentifier":"1d5b88aec8a3e1c4c57071307b2dae3a"},{"imageIdentifier":"a501051db16e3cbf88ea50bfb0138a47"}\]}$# |
            | {"abv":{"$gt":9}} | #^{"search":{.*},"images":\[{"imageIdentifier":"f3210f1bb34bfbfa432cc3560be40761"}\]}$# |
            | {"abv":{"$gte":9}} | #^{"search":{.*},"images":\[{"imageIdentifier":"b914b28f4d5faa516e2049b9a6a2577c"},{"imageIdentifier":"f3210f1bb34bfbfa432cc3560be40761"}\]}$# |
            | {"abv":{"$lt":4.7}} | #^{"search":{.*},"images":\[{"imageIdentifier":"a501051db16e3cbf88ea50bfb0138a47"}\]}$# |
            | {"abv":{"$lte":4.7}} | #^{"search":{.*},"images":\[{"imageIdentifier":"1d5b88aec8a3e1c4c57071307b2dae3a"},{"imageIdentifier":"a501051db16e3cbf88ea50bfb0138a47"}\]}$# |
            | {"style":{"$in":["IPA","Witbier"]}} | #^{"search":{.*},"images":\[{"imageIdentifier":"1d5b88aec8a3e1c4c57071307b2dae3a"},{"imageIdentifier":"929db9c5fc3099f7576f5655207eba47"},{"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2"}\]}$# |
            | {"beer":{"$wildcard":"Dark*"}} | #^{"search":{.*},"images":\[{"imageIdentifier":"b914b28f4d5faa516e2049b9a6a2577c"},{"imageIdentifier":"f3210f1bb34bfbfa432cc3560be40761"}\]}$# |
            | {"$and":[{"brewery":"Nøgne Ø"},{"beer":"Dark Horizon"}]} | #^{"search":{.*},"images":\[{"imageIdentifier":"f3210f1bb34bfbfa432cc3560be40761"}\]}$# |
            | {"$or":[{"brewery":"Nøgne Ø"},{"beer":"Dark Force"}]} | #^{"search":{.*},"images":\[{"imageIdentifier":"b914b28f4d5faa516e2049b9a6a2577c"},{"imageIdentifier":"f3210f1bb34bfbfa432cc3560be40761"},{"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2"}\]}$# |
            | {"review":{"$exists":true}} | #^{"search":{.*},"images":\[{"imageIdentifier":"b914b28f4d5faa516e2049b9a6a2577c"},{"imageIdentifier":"f3210f1bb34bfbfa432cc3560be40761"},{"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2"}\]}$# |
            | {"review":{"$exists":false}} | #^{"search":{.*},"images":\[{"imageIdentifier":"1d5b88aec8a3e1c4c57071307b2dae3a"},{"imageIdentifier":"929db9c5fc3099f7576f5655207eba47"},{"imageIdentifier":"a501051db16e3cbf88ea50bfb0138a47"}\]}$# |
            | {"beer":{"$ne":"Witbier"},"$or":[{"brewery":"Nøgne Ø"},{"$and":[{"abv":{"$gte":5.5}},{"style":{"$in":["IPA","Imperial Stout"]}},{"brewery":{"$in":["HaandBryggeriet","Ægir","Lervig","Kinn"]}}]}]} | #^{"search":{.*},"images":\[{"imageIdentifier":"929db9c5fc3099f7576f5655207eba47"},{"imageIdentifier":"b914b28f4d5faa516e2049b9a6a2577c"},{"imageIdentifier":"f3210f1bb34bfbfa432cc3560be40761"},{"imageIdentifier":"fc7d2d06993047a0b5056e8fac4462a2"}\]}$# |
