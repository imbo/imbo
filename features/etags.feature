Feature: Imbo add ETag's to responses
    In order to improve client side performance
    As an image server
    I can specify ETag's in all responses

    Background:
        Given "tests/Fixtures/image.png" exists in Imbo

    Scenario Outline: Take the Accept header into consideration when generating an ETag for an image URI without an extension
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "<acceptHeader>"
        When I request "/users/publickey/images/929db9c5fc3099f7576f5655207eba47"
        Then I should get a response with "200 OK"
        And the "ETag" response header is "<etag>"

        Examples:
            | acceptHeader | etag  |
            | */*          | "8fd1bf3c31be219e1fe4bc19f7fa8f39" |
            | image/*      | "473d84adfa3b3f2a982bfed814810f23" |
            | image/jpeg   | "812f2331e768b55139b572a83550def8" |
            | image/gif    | "879728b55ce073e49779b4a1bac27280" |
            | image/png    | "f420cf294318e4d37b1b87da3d840ba6" |


    Scenario Outline: Don't take the Accept header into consideration when generating an ETag for an image URI with an extension
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        And the "Accept" request header is "<acceptHeader>"
        When I request "/users/publickey/images/929db9c5fc3099f7576f5655207eba47.png"
        Then I should get a response with "200 OK"
        And the "ETag" response header is "<etag>"

        Examples:
            | acceptHeader | etag  |
            | */*          | "2e06bf38756520604152772271f3afca" |
            | image/*      | "2e06bf38756520604152772271f3afca" |
            | image/jpeg   | "2e06bf38756520604152772271f3afca" |
            | image/gif    | "2e06bf38756520604152772271f3afca" |
            | image/png    | "2e06bf38756520604152772271f3afca" |
