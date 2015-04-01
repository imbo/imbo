Feature: Imbo enables caching of transformations
    In order to speed up image transformations
    As an image server
    I will cache and re-use transformed images

    Background:
        Given "tests/phpunit/Fixtures/image1.png" is used as the test image

    Scenario: Fetch the image with a specific extension that is not in the cache
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And Imbo uses the "image-transformation-cache.php" configuration
        When I request the test image as a "jpg"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/jpeg"
        And the "X-Imbo-TransformationCache" response header is "Miss"
        And the checksum of the image is "91a5b594c08540bd912123cc61154e1c"

    Scenario: Perform the same request again
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And Imbo uses the "image-transformation-cache.php" configuration
        When I request the test image as a "jpg"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/jpeg"
        And the "X-Imbo-TransformationCache" response header is "Hit"
        And the checksum of the image is "91a5b594c08540bd912123cc61154e1c"

    Scenario: Fetch the same image, but with a different extension
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And Imbo uses the "image-transformation-cache.php" configuration
        When I request the test image as a "png"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/png"
        And the "X-Imbo-TransformationCache" response header is "Miss"
        And the checksum of the image is "fc7d2d06993047a0b5056e8fac4462a2"

    Scenario: Perform the same request again
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And Imbo uses the "image-transformation-cache.php" configuration
        When I request the test image as a "png"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/png"
        And the "X-Imbo-TransformationCache" response header is "Hit"
        And the checksum of the image is "fc7d2d06993047a0b5056e8fac4462a2"

    Scenario: Fetch image with extra transformations added
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And I specify "crop:width=50,height=60,x=1,y=10" as transformation
        And Imbo uses the "image-transformation-cache.php" configuration
        When I request the test image as a "jpg"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/jpeg"
        And the "X-Imbo-TransformationCache" response header is "Miss"
        And the checksum of the image is "2681f6eafb6fad2079df701ba90cf377"

    Scenario: Perform the same request again
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And I specify "crop:width=50,height=60,x=1,y=10" as transformation
        And Imbo uses the "image-transformation-cache.php" configuration
        When I request the test image as a "jpg"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/jpeg"
        And the "X-Imbo-TransformationCache" response header is "Hit"
        And the checksum of the image is "2681f6eafb6fad2079df701ba90cf377"

    Scenario: Delete an image, which will also delete the transformed images from the cache
        Given I use "publickey" and "privatekey" for public and private keys
        And Imbo uses the "image-transformation-cache.php" configuration
        And I sign the request
        When I request the test image using HTTP "DELETE"
        Then I should get a response with "200 OK"

    Scenario: Fetch the image with a .jpg extension once more
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And Imbo uses the "image-transformation-cache.php" configuration
        When I request the test image as a "jpg"
        Then I should get a response with "404 Image not found"
