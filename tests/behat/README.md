# Integration tests

Imbos integration tests are implemented as several [Behat](http://behat.org) test suites with the help of [imbo/behat-api-extension](https://github.com/imbo/behat-api-extension). All suites are defined in the `behat.yml.dist` configuration file. Some suites require local configuration before they can be executed, as in database access and so forth.

## Configuration

The `behat.yml.dist` file in the project root specifies the `base_uri` for the Imbo installation, and by default it is set to `http://localhost:8080`. If you wish to run the tests using a different host and/or port you will need to create a `behat.yml` configuration file in the project root that specifies the host/port combination you want to use:

```yaml
imports:
    - behat.yml.dist

default:
    extensions:
        Imbo\BehatApiExtension:
            apiClient:
                base_uri: http://localhost:8081
```

The main configuration file also defines all tests suites, which are basically different combinations of database and storage adapters. Some suites have settings defined in the configuration file, and if you wish to override some of these you can use the same method as above:

```yaml
imports:
    - behat.yml.dist

default:
    suites:
        doctrine_mysql_filesystem:
            database.username: custom-username
            database.password: custom-password
```

## Run tests

To run all test suites, execute the following command from the project root:

    ./vendor/bin/behat --strict

or

    composer test-behat

For the tests to run you need to have an HTTPD running that hosts the Imbo installation. A composer script has been created for this purpose:

    composer run dev

which simply executes:

    php -S localhost:8080 -t ./public tests/behat/router.php > build/logs/httpd.log 2>&1 &

The `behat.yml.dist` configuration file contains several test suites, and if you only want to run one of them, use the `--suite <suite>` parameter when executing Behat:

    ./vendor/bin/behat --suite <suite> --strict

### Authentication

The test configuration specifies the following authentication information that is used in the tests:

| Public key      | Private key   | Access to            |
| --------------- | ------------- | -------------------- |
| `publickey`     | `privatekey`  | `user`, `other-user` |
| `unpriviledged` | `privatekey`  | `user`               |
| `wildcard`      | `*`           | `*`                  |

Feel free to add more authentication information if you create tests that needs a different set of keys / users.

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
Given :imagePath exists for user :user with the following metadata: <PyStringNode>
Given the client IP is :ip
Given I specify :transformation as transformation
Given I specify the following transformations: <PyStringNode>
Given I prime the database with :fixture
Given I authenticate using :method
Given I use :publicKey and :privateKey for public and private keys
Given the query string parameter :name is set to :value
Given the query string parameter :param is set to the image identifier of :path
Given I generate a short URL for :path with the following parameters: <PyStringNode>
Given I use :localPath as the watermark image
Given I use :localPath as the watermark image with :params as parameters

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
When I request the image using the generated short URL

Then the Imbo error message is :message
Then the Imbo error message is :message and the error code is :code
Then the image width is :width
Then the image height is :height
Then the image dimension is :dimension
Then the pixel at coordinate :coordinates has a color of :color
Then the pixel at coordinate :coordinates has an alpha of :alpha
Then the ACL rule under public key :publicKey with ID :aclId no longer exists
Then the :publicKey public key no longer exists
Then the response can be cached
Then the response can not be cached
Then the response has a max-age of :max seconds
Then the response has a :directive cache-control directive
Then the response does not have a :directive cache-control directive
Then the last :num :headerName response headers are the same
Then the last :num :headerName response headers are not the same
Then the last responses match: <TableNode>
Then the image should not have any :prefix properties
Then the response body size is :expectedSize
```
