Feature: Imbo enables caching of transformations
    In order to speed up image transformations
    As an image server
    I will cache and re-use transformed images

    Scenario: Fetch an image not present in the cache
        Given "tests/Fixtures/image1.png" exists in Imbo with identifier "fc7d2d06993047a0b5056e8fac4462a2"
        And I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2.jpg"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/jpeg"
        And the "X-Imbo-TransformationCache" response header is "Miss"

    Scenario: Fetch the same image again, which will be retrieved from the cache
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2.jpg"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/jpeg"
        And the "X-Imbo-TransformationCache" response header is "Hit"

    Scenario: Fetch the same image, but with a different extension
        Given "tests/Fixtures/image1.png" exists in Imbo with identifier "fc7d2d06993047a0b5056e8fac4462a2"
        And I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2.png"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/png"
        And the "X-Imbo-TransformationCache" response header is "Miss"

    Scenario: Fetch same image with different transformations
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And I specify "crop:width=50,height=60,x=1,y=10" as transformation
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2.jpg"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/jpeg"
        And the "X-Imbo-TransformationCache" response header is "Miss"

    Scenario: Delete an image, which will also delete transformed images from the cache
        Given I use "publickey" and "privatekey" for public and private keys
        And I sign the request
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2" using HTTP "DELETE"
        Then I should get a response with "200 OK"

    Scenario: Fetch the same image, which is no longer in the cache
        Given "tests/Fixtures/image1.png" exists in Imbo with identifier "fc7d2d06993047a0b5056e8fac4462a2"
        And I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2.png"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/png"
        And the "X-Imbo-TransformationCache" response header is "Miss"

