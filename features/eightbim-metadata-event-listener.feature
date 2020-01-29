Feature: Imbo provides an event listener for turning 8BIM data into metadata
  In order to automatically add 8BIM-data as metadata
  As an Imbo admin
  I must enable the EightbimMetadata event listener

  Background:
    Given Imbo uses the "add-8bim-data-as-metadata.php" configuration

  Scenario: Fetch the added metadata
    Given "tests/Fixtures/jpeg-with-multiple-paths.jpg" exists for user "user"
    And I use "publicKey" and "privateKey" for public and private keys
    And I include an access token in the query string
    When I request the metadata of the previously added image
    Then the response status line is "200 OK"
    And the "Content-Type" response header is "application/json"
    And the response body contains JSON:
            """
            {
              "paths": ["House", "Panda"]
            }
            """

