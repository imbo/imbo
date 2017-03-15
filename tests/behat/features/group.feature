Feature: Imbo provides a group endpoint
    In order to query resource groups
    As an HTTP Client
    I want to make requests against the group endpoint

    Background:
        Given Imbo uses the "access-control.php" configuration

    Scenario Outline: Fetch resources of a group
        Given I use "valid-group-pubkey" and "foobar" for public and private keys
        And I include an access token in the query
        When I request "/groups/images-read.<extension>"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "<content-type>"
        And the response body matches:
        """
        <response>
        """
        Examples:
            | extension | content-type     | response |
            | json      | application/json | #^{"name":"images-read","resources":\["images\.get","images\.head"]}$# |

    Scenario Outline: Create a resource group with invalid data
        Given Imbo uses the "access-control-mutable.php" configuration
        And I prime the database with "access-control-mutable.php"
        And I use "acl-creator" and "someprivkey" for public and private keys
        And the request body contains:
          """
          <data>
          """
        And I sign the request
        When I request "/groups/read-images" using HTTP "PUT"
        Then the response status line is "<response>"
        Examples:
            | data               | response                                                           |
            |                    | 400 Invalid data. Array of resource strings is expected            |
            | true               | 400 Invalid data. Array of resource strings is expected            |
            | "string"           | 400 Invalid data. Array of resource strings is expected            |
            | [123]              | 400 Invalid value in the resources array. Only strings are allowed |
            | [123,"images.get"] | 400 Invalid value in the resources array. Only strings are allowed |

    Scenario: Create a resource group
        Given Imbo uses the "access-control-mutable.php" configuration
        And I prime the database with "access-control-mutable.php"
        And I use "acl-creator" and "someprivkey" for public and private keys
        And the request body contains:
          """
          ["images.get"]
          """
        And I sign the request
        When I request "/groups/read-images" using HTTP "PUT"
        Then the response status line is "201 Created"

    Scenario: Update a resource group
        Given Imbo uses the "access-control-mutable.php" configuration
        And I prime the database with "access-control-mutable.php"
        And I use "acl-creator" and "someprivkey" for public and private keys
        And the request body contains:
          """
          ["images.get", "images.head"]
          """
        And I sign the request
        When I request "/groups/existing-group" using HTTP "PUT"
        Then the response status line is "200 OK"

    Scenario: Delete a resource group
        Given Imbo uses the "access-control-mutable.php" configuration
        And I prime the database with "access-control-mutable.php"
        And I use "acl-creator" and "someprivkey" for public and private keys
        And I sign the request
        When I request "/groups/existing-group" using HTTP "DELETE"
        Then the response status line is "200 OK"

    Scenario: Delete a resource group that has access-control rules that depends on it
        Given Imbo uses the "access-control-mutable.php" configuration
        And I prime the database with "access-control-mutable.php"
        And I use "acl-creator" and "someprivkey" for public and private keys
        And I sign the request
        When I request "/groups/user-stats" using HTTP "DELETE"
        Then the response status line is "200 OK"
        And the ACL rule under public key "group-based" with ID "100000000000000000001942" should not exist anymore

    Scenario: Delete a resource group with an immutable access control adapter
        Given I use "valid-group-pubkey" and "foobar" for public and private keys
        And I sign the request
        When I request "/groups/groups-read" using HTTP "DELETE"
        Then the response status line is "405 Access control adapter is immutable"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Access control adapter is immutable" and the error code is "0"

    Scenario: Update a resource group with an immutable access control adapter
        Given I use "valid-group-pubkey" and "foobar" for public and private keys
        And the request body contains:
          """
          ["images.get"]
          """
        And I sign the request
        When I request "/groups/groups-read" using HTTP "PUT"
        Then the response status line is "405 Access control adapter is immutable"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Access control adapter is immutable" and the error code is "0"
