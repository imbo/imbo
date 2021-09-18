Feature: Imbo provides a group endpoint
    In order to query resource groups
    As an HTTP Client
    I want to make requests against the group endpoint

    Background:
        Given Imbo uses the "access-control.php" configuration

    Scenario: Fetch resources of a group
        Given I use "valid-group-pubkey" and "foobar" for public and private keys
        And I include an access token in the query string
        When I request "/groups/images-read.json"
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
              "name": "images-read",
              "resources": ["images.get", "images.head"]
            }
            """

    Scenario Outline: Create a resource group with invalid data
        Given Imbo uses the "access-control-mutable.php" configuration
        And I prime the database with "access-control-mutable.php"
        And I use "acl-creator" and "someprivkey" for public and private keys
        And I sign the request
        And the request body is:
          """
          <data>
          """
        When I request "/groups" using HTTP "POST"
        Then the response status line is "<response>"

        Examples:
            | data                                                     | response                                                           |
            | {}                                                       | 400 Group name missing                                             |
            |                                                          | 400 Missing JSON data                                              |
            | {"name": "read-images"}                                  | 400 Resource list missing                                          |
            | {"name": "read-images", "resources": "image.get"}        | 400 Resource list missing                                          |
            | {"name": "read-images", "resources": ["image.get", 123]} | 400 Resources must be specified as strings                         |

    Scenario: Create a resource group
        Given Imbo uses the "access-control-mutable.php" configuration
        And I prime the database with "access-control-mutable.php"
        And I use "acl-creator" and "someprivkey" for public and private keys
        And I sign the request
        And the request body is:
          """
          {"name": "read-images", "resources": ["images.get"]}
          """
        When I request "/groups" using HTTP "POST"
        Then the response status line is "201 Created"

    Scenario: Update a resource group
        Given Imbo uses the "access-control-mutable.php" configuration
        And I prime the database with "access-control-mutable.php"
        And I use "acl-creator" and "someprivkey" for public and private keys
        And I sign the request
        And the request body is:
          """
          {"resources": ["images.get", "images.head"]}
          """
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
        And I use "master-pubkey" and "master-privkey" for public and private keys
        And I sign the request
        When I request "/groups/user-stats" using HTTP "DELETE"
        Then the response status line is "200 OK"
        And the ACL rule under public key "group-based" with ID "100000000000000000001942" no longer exists

    Scenario: Delete a resource group with an immutable access control adapter
        Given I use "valid-group-pubkey" and "foobar" for public and private keys
        And I sign the request
        When I request "/groups/groups-read" using HTTP "DELETE"
        Then the response status line is "405 Access control adapter is immutable"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Access control adapter is immutable" and the error code is "0"

    Scenario: Update a resource group with an immutable access control adapter
        Given I use "valid-group-pubkey" and "foobar" for public and private keys
        And I sign the request
        And the request body is:
          """
          ["images.get"]
          """
        When I request "/groups/groups-read" using HTTP "PUT"
        Then the response status line is "405 Access control adapter is immutable"
        And the "Content-Type" response header is "application/json"
        And the Imbo error message is "Access control adapter is immutable" and the error code is "0"
