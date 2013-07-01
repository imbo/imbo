Feature: Imbo provides short urls for images
    In order to use shorter image URLs
    As a client
    I will use the custom header provided by Imbo

    Background:
        Given "tests/Imbo/Fixtures/image1.png" exists in Imbo with identifier "fc7d2d06993047a0b5056e8fac4462a2"

    Scenario: Request an image to get the short URL
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2.jpg" using HTTP "HEAD"
        Then I should get a response with "200 OK"
        And the "X-Imbo-ShortUrl" response header matches "http://localhost:8888/s/[a-zA-Z0-9]{7}"

    Scenario: Request an image using the short URL
        Given I fetch the short URL of "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2.gif?t[]=thumbnail&t[]=border"
        When I request the image using the short URL
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "image/gif"
        And the "X-Imbo-Originalextension" response header is "png"
        And the "X-Imbo-Originalfilesize" response header is "95576"
        And the "X-Imbo-Originalheight" response header is "417"
        And the "X-Imbo-Originalmimetype" response header is "image/png"
        And the "X-Imbo-Originalwidth" response header is "599"

    Scenario: Request a non-existing image using the short URL
        Given I fetch the short URL of "/users/publickey/images/fc7d2d06993047a0b5056e8fac4462a2.jpg?t[]=resize:width=400"
        And the image is deleted
        When I request the image using the short URL
        Then I should get a response with "404 Image not found"
