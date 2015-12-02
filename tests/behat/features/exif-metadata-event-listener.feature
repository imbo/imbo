Feature: Imbo provides an event listener for turning EXIF data into metadata
    In order to automatically add EXIF-data as metadata
    As an Imbo admin
    I must enable the ExifMetadata  event listener

    Background:
        Given Imbo uses the "add-exif-data-as-metadata.php" configuration

    Scenario: Fetch the added metadata
        Given "tests/phpunit/Fixtures/exif-logo.jpg" exists in Imbo
        And I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request the metadata of the previously added image
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains:
        """
        "exif:Make":"SAMSUNG"
        """
        And the response body contains:
        """
        "exif:Model":"GT-I9100"
        """
        And the response body contains:
        """
        "exif:GPSAltitude":"254\/5"
        """
        And the response body contains:
        """
        "exif:GPSLatitude":"63\/1, 40\/1, 173857\/3507"
        """
        And the response body contains:
        """
        "exif:GPSLongitude":"9\/1, 5\/1, 38109\/12500"
        """
        And the response body contains:
        """
        "gps:location":[9.0841802,63.680437300003]
        """
        And the response body contains:
        """
        "gps:altitude":50.8
        """

    Scenario: Metadata is normalized
        Given "tests/phpunit/Fixtures/logo-horizontal.png" exists in Imbo
        And I use "publickey" and "privatekey" for public and private keys
        And I include an access token in the query
        When I request the metadata of the previously added image
        Then I should get a response with "200 OK"
        And the "Content-Type" response header is "application/json"
        And the response body contains:
        """
        "png:IHDR:bit-depth":"8"
        """
