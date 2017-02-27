# Behat features
The `features` directory contains all the feature files that is tested with Behat. [imbo/behat-api-extension](https://github.com/imbo/behat-api-extension) is used to test the API, and the [FeatureContext](features/bootstrap/FeatureContext.php) class contains a series of steps that can be used to test Imbo-specific features. It also contains steps for priming Imbo with given images and metadata.

## Custom steps

The following is a list of steps implemented in the `FeatureContext` class:

```gherkin
Given Imbo uses the :configFile configuration
Given the stats are allowed by :mask
Given the storage is down
Given the database is down
Given I use :publicKey and :privateKey for public and private keys
Given I sign the request
Given I sign the request using HTTP headers
Given I include an access token in the query string
Given :imagePath exists in Imbo
Given :imagePath exists for user :user in Imbo
Given the client IP is :ip
When I request the previously added image
When I request the previously added image using HTTP :method
Then the Imbo error message is :message
Then the Imbo error message is :message and the error code is :code
```

## Run tests

To run the complete testsuite, execute the following command from the project root:

    ./vendor/bin/behat --strict

or

    composer test-behat

For the tests to run you need to have an HTTPD running that hosts the Imbo installation. A composer script has been created for this purpose:

    composer start-httpd-for-behat-tests

which simply executes:

    php -S localhost:8080 -t ./public tests/behat/router.php > build/logs/httpd.log 2>&1 &

## Configuration
The `behat.yml.dist` file in the project root specifies the `base_uri` for the Imbo installation, and by default it is set to `http://localhost:8080`. If you wish to run the testsuite using a different host and/or port you will need to create a `behat.yml` configuration file in the project root that specifies the host/port combination you want to use. Remember to use the router script specified above for the tests to work as expected. This script makes sure that Imbo uses the correct configuration with regards to testing, and is also responsible for adding custom configuration based on steps defined in the FeatureContext class.

### Authentication

The test configuration specifies the following authentication information that is used in the tests:

| Public key      | Private key   | Access to            |
| --------------- | ------------- | -------------------- |
| `publickey`     | `privatekey`  | `user`, `other-user` |
| `unpriviledged` | `privatekey`  | `user`               |
| `wildcard`      | `*`           | `*`                  |

Feel free to add more authentication information if you create tests that needs a different set of keys / users.
