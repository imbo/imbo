# Behat features
The `features` directory contains all the feature files that is tested with Behat. [imbo/behat-api-extension](https://github.com/imbo/behat-api-extension) is used to test the API, and the [FeatureContext](features/bootstrap/FeatureContext.php) class contains a series of steps that can be used to test Imbo-specific features. It also contains steps for priming Imbo with given images and metadata.

## Custom steps

The following is a list of steps implemented in the `FeatureContext` class:

```gherkin
Given Imbo uses the :configFile configuration
Given the stats are allowed by :mask
Given the storage is down
Given the database is down
Given I sign the request using HTTP headers
Given I sign the request
Given I include an access token in the query string
Given I include an access token in the query string for all requests
Given :imagePath exists for user :user
Given :imagePath exists for user :user with the following metadata:
Given the client IP is :ip
Given I specify :transformation as transformation
Given I specify the following transformations: <PyStringNode>
Given the pixel at coordinate :coordinates has an alpha of :alpha
Given I prime the database with :fixture
Given I authenticate using :method
Given I use :publicKey and :privateKey for public and private keys
Given the query string parameter :name is set to :value
Given the query string parameter :param is set to the image identifier of :path

When I request the previously added image
When I request the previously added image using HTTP :method
When I request the previously added image as a :extension
When I request the previously added image as a :extension using HTTP :method
When I replay the last request
When I replay the last request using HTTP :method
When I request the metadata of the previously added image
When I request the metadata of the previously added image using HTTP :method
When I request the image resource for :path
When I request the image resource for :path using HTTP :method
When I request the image resource for :path as a (png|gif|jpg)
When I request the image resource for :path as a (png|gif|jpg) using HTTP :method
When I request: <TableNode>

Then the Imbo error message is :message
Then the Imbo error message is :message and the error code is :code
Then the image width is :width
Then the image height is :height
Then the image dimension is :dimension
Then the pixel at coordinate :coordinates has a color of :color
Then the ACL rule under public key :publicKey with ID :aclId no longer exists
Then the :publicKey public key no longer exists
Then the response can be cached
Then the response can not be cached
Then the response has a max-age of :max seconds
Then the response has a :directive cache-control directive
Then the response does not have a :directive cache-control directive
Then the last :num :headerName response headers are the same
Then the last :num :headerName response headers are not the same
Then the last :num responses match: <TableNode>
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
