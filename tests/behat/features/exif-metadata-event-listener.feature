Feature: Imbo provides an event listener for turning EXIF data into metadata
    In order to automatically add EXIF-data as metadata
    As an Imbo admin
    I must enable the ExifMetadata  event listener

    Background:
        Given Imbo uses the "add-exif-data-as-metadata.php" configuration

    Scenario: Fetch the added metadata
        Given "tests/phpunit/Fixtures/exif-logo.jpg" exists for user "user"
        And I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request the metadata of the previously added image
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
              "exif:Make": "SAMSUNG",
              "exif:Model": "GT-I9100",
              "exif:GPSAltitude": "254\/5",
              "exif:GPSLatitude": "63\/1, 40\/1, 173857\/3507",
              "exif:GPSLongitude": "9\/1, 5\/1, 38109\/12500",
              "gps:location":
              [
                9.0841802,
                63.680437300003
              ],
              "gps:altitude": 50.8
            }
            """

    Scenario: Metadata is normalized
        Given "tests/phpunit/Fixtures/logo-horizontal.png" exists for user "user"
        And I use "publicKey" and "privateKey" for public and private keys
        And I include an access token in the query string
        When I request the metadata of the previously added image
        Then the response status line is "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains JSON:
            """
            {
              "png:IHDR:bit-depth": "8"
            }
            """
