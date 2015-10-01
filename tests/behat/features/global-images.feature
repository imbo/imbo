Feature: Imbo provides a global images endpoint
    In order to query images
    As an HTTP Client
    I want to make requests against the images endpoint

    Background:
        Given Imbo starts with an empty database
        And "tests/phpunit/Fixtures/image1.png" exists for user "user" in Imbo
        And "tests/phpunit/Fixtures/image.jpg" exists for user "user" in Imbo
        And "tests/phpunit/Fixtures/image.gif" exists for user "other-user" in Imbo
        And "tests/phpunit/Fixtures/1024x256.png" exists for user "other-user" in Imbo

    Scenario: Fetch images without specifying any users
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/images.json"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
        """
        #^{"search":{"hits":0#
        """

    Scenario Outline: Fetch images specifying users
        Given I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/images.json<queryString>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | queryString                    | response               |
            | ?user[]=user&user[]=other-user | #^{"search":{"hits":4,"page":1,"limit":20,"count":4}# |
            | ?user[]=user                   | #^{"search":{"hits":2,"page":1,"limit":20,"count":2}# |
            | ?user[]=other-user             | #^{"search":{"hits":2,"page":1,"limit":20,"count":2}# |

    Scenario Outline: Fetch images specifying a user without having access to it
        Given I use "unpriviledged" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request "/images.json<queryString>"
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body matches:
        """
        <response>
        """

        Examples:
            | queryString                    | response               |
            | ?user[]=user&user[]=other-user | #^{"search":{"hits":2,"page":1,"limit":20,"count":2}# |
            | ?user[]=user                   | #^{"search":{"hits":2,"page":1,"limit":20,"count":2}# |
            | ?user[]=other-user             | #^{"search":{"hits":0,"page":1,"limit":20,"count":0}# |
